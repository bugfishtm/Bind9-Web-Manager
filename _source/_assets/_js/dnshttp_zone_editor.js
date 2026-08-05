/*
	.........%%%%%...%%..%%...%%%%...%%..%%..%%%%%%..%%%%%%..%%%%%..
	.........%%..%%..%%%.%%..%%......%%..%%....%%......%%....%%..%%.
	.........%%..%%..%%.%%%...%%%%...%%%%%%....%%......%%....%%%%%..
	.........%%..%%..%%..%%......%%..%%..%%....%%......%%....%%.....
	.........%%%%%...%%..%%...%%%%...%%..%%....%%......%%....%%.....
	................................................................
				PHP DNS Software by Jan-Maurice "Bugfish" Dahlmanns

	#	Copyright (C) 2026 Jan Maurice Dahlmanns [Bugfish]

	#	This program is free software: you can redistribute it and/or modify
	#	it under the terms of the GNU General Public License as published by
	#	the Free Software Foundation, either version 3 of the License, or
	#	(at your option) any later version.

	#	This program is distributed in the hope that it will be useful,
	#	but WITHOUT ANY WARRANTY; without even the implied warranty of
	#	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	#	GNU General Public License for more details.

	#	You should have received a copy of the GNU General Public License
	#	along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

/* ##################################################################
	DNSHTTP Zone Record Editor

	Renders the zone text of a domain as an editable record table and
	writes it back as a bind master file. Mounted on the element with
	the id "dns-zone-editor", the zone text comes in through its
	data-zone attribute and goes back out through
	window.DNSZoneEditor.getZoneData().

	Two rules this file lives by:

	1. Nothing the user did not touch is ever rewritten. Every line
	   read from the zone keeps its original text and is written back
	   byte for byte unless that record was actually edited. Comments,
	   $GENERATE, unknown record types, odd spacing - all of it
	   survives a load/save round trip. Silently dropping a line here
	   means deleting DNS data on the next cronjob run.

	2. The editor never blocks a zone. It warns, explains and points
	   at the record, but bind (named-checkzone in the sync run) stays
	   the authority on what is valid.
################################################################## */

(function () {
'use strict';

/* ##################################################################
	Punycode / IDN (RFC 3492)
	------------------------------------------------------------------
	Zone files carry ASCII (A-label) names. Somebody maintaining
	muenchen.example has to be able to type it the way they know it,
	and has to recognise xn--mnchen-3ya.example when they see it, so
	names are converted on input and shown decoded next to the field.

	This is UTS-46 "lite": NFC normalisation plus lowercasing, which
	covers the cases a zone editor sees. The authoritative conversion
	of the domain name itself is done server side with intl.
################################################################## */
const PUNY = (function () {
	const base = 36, tMin = 1, tMax = 26, skew = 38, damp = 700;
	const initialBias = 72, initialN = 128, delimiter = '-';

	function adapt(delta, numPoints, firstTime) {
		delta = firstTime ? Math.floor(delta / damp) : delta >> 1;
		delta += Math.floor(delta / numPoints);
		let k = 0;
		while (delta > ((base - tMin) * tMax) >> 1) {
			delta = Math.floor(delta / (base - tMin));
			k += base;
		}
		return k + Math.floor(((base - tMin + 1) * delta) / (delta + skew));
	}

	function digitToBasic(d) { return String.fromCharCode(d + 22 + (d < 26 ? 75 : 0)); }

	function basicToDigit(cp) {
		if (cp >= 0x30 && cp <= 0x39) return cp - 22;   /* 0-9 */
		if (cp >= 0x41 && cp <= 0x5A) return cp - 65;   /* A-Z */
		if (cp >= 0x61 && cp <= 0x7A) return cp - 97;   /* a-z */
		return base;
	}

	function encode(input) {
		const cps = Array.from(input).map(c => c.codePointAt(0));
		const out = [];
		let n = initialN, delta = 0, bias = initialBias;
		for (const c of cps) { if (c < 0x80) out.push(String.fromCodePoint(c)); }
		let h = out.length;
		const b = h;
		if (b > 0) out.push(delimiter);
		while (h < cps.length) {
			let m = Infinity;
			for (const c of cps) { if (c >= n && c < m) m = c; }
			delta += (m - n) * (h + 1);
			n = m;
			for (const c of cps) {
				if (c < n) delta++;
				if (c === n) {
					let q = delta;
					for (let k = base; ; k += base) {
						const t = k <= bias ? tMin : (k >= bias + tMax ? tMax : k - bias);
						if (q < t) break;
						out.push(digitToBasic(t + ((q - t) % (base - t))));
						q = Math.floor((q - t) / (base - t));
					}
					out.push(digitToBasic(q));
					bias = adapt(delta, h + 1, h === b);
					delta = 0;
					h++;
				}
			}
			delta++;
			n++;
		}
		return out.join('');
	}

	function decode(input) {
		const out = [];
		let n = initialN, i = 0, bias = initialBias;
		let basicEnd = input.lastIndexOf(delimiter);
		if (basicEnd < 0) basicEnd = 0;
		for (let j = 0; j < basicEnd; j++) {
			const cp = input.charCodeAt(j);
			if (cp >= 0x80) return null;
			out.push(cp);
		}
		for (let idx = basicEnd > 0 ? basicEnd + 1 : 0; idx < input.length;) {
			const oldi = i;
			let w = 1;
			for (let k = base; ; k += base) {
				if (idx >= input.length) return null;
				const digit = basicToDigit(input.charCodeAt(idx++));
				if (digit >= base) return null;
				i += digit * w;
				const t = k <= bias ? tMin : (k >= bias + tMax ? tMax : k - bias);
				if (digit < t) break;
				w *= (base - t);
				if (w > 0x7FFFFFFF) return null;
			}
			bias = adapt(i - oldi, out.length + 1, oldi === 0);
			n += Math.floor(i / (out.length + 1));
			if (n > 0x10FFFF) return null;
			i %= (out.length + 1);
			out.splice(i, 0, n);
			i++;
		}
		try { return out.map(c => String.fromCodePoint(c)).join(''); } catch (e) { return null; }
	}

	return { encode: encode, decode: decode };
})();

/* Is there anything in this string that is not plain ASCII? */
function hasUnicode(s) { return /[^\x00-\x7F]/.test(String(s || '')); }

/* Unicode name -> A-label form. Labels that are already ASCII are left alone,
   which keeps escapes like \032 and the underscore labels of SRV/TLSA intact. */
function nameToASCII(name) {
	const s = String(name || '');
	if (!hasUnicode(s)) return s;
	return s.split('.').map(function (label) {
		if (!hasUnicode(label)) return label;
		const norm = label.normalize ? label.normalize('NFC').toLowerCase() : label.toLowerCase();
		const enc = PUNY.encode(norm);
		return enc === norm ? norm : 'xn--' + enc;
	}).join('.');
}

/* A-label form -> Unicode, for display only. Returns null when nothing changed. */
function nameToUnicode(name) {
	const s = String(name || '');
	if (!/(^|\.)xn--/i.test(s)) return null;
	let changed = false;
	const out = s.split('.').map(function (label) {
		if (!/^xn--/i.test(label)) return label;
		const dec = PUNY.decode(label.slice(4).toLowerCase());
		if (dec === null || dec === '') return label;
		changed = true;
		return dec;
	}).join('.');
	return changed ? out : null;
}

/* ##################################################################
	Small helpers
################################################################## */
function uid() { return 'r' + (uid._n = (uid._n || 0) + 1) + Math.random().toString(36).slice(2, 7); }

function esc(s) {
	return String(s === null || s === undefined ? '' : s)
		.replace(/&/g, '&amp;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#39;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;');
}

/* Bind time values: a plain number of seconds, or unit groups like 1w2d3h.
   Returns null when the text is not a time value at all. */
function parseTTL(val) {
	if (val === null || val === undefined) return null;
	const s = String(val).trim();
	if (s === '') return null;
	if (/^\d+$/.test(s)) {
		const n = parseInt(s, 10);
		return isFinite(n) ? n : null;
	}
	if (!/^(\d+[smhdwSMHDW])+$/.test(s)) return null;
	const unit = { s: 1, m: 60, h: 3600, d: 86400, w: 604800 };
	let total = 0;
	const re = /(\d+)([smhdwSMHDW])/g;
	let m;
	while ((m = re.exec(s)) !== null) { total += parseInt(m[1], 10) * unit[m[2].toLowerCase()]; }
	return total;
}

function isTTLToken(s) { return /^\d+$/.test(s) || /^(\d+[smhdwSMHDW])+$/.test(s); }

/* Seconds -> the shortest exact bind notation, so 86400 reads as 1d. */
function formatTTL(secs) {
	const n = parseInt(secs, 10);
	if (!isFinite(n) || n < 0) return String(secs);
	if (n === 0) return '0';
	if (n % 604800 === 0) return (n / 604800) + 'w';
	if (n % 86400 === 0) return (n / 86400) + 'd';
	if (n % 3600 === 0) return (n / 3600) + 'h';
	if (n % 60 === 0) return (n / 60) + 'm';
	return String(n);
}

/* Human readable duration for the tooltip next to a TTL. */
function humanTTL(secs) {
	const n = parseInt(secs, 10);
	if (!isFinite(n)) return '';
	if (n === 0) return 'no caching';
	const parts = [];
	const units = [['week', 604800], ['day', 86400], ['hour', 3600], ['minute', 60], ['second', 1]];
	let rest = n;
	for (const u of units) {
		const c = Math.floor(rest / u[1]);
		if (c > 0) { parts.push(c + ' ' + u[0] + (c !== 1 ? 's' : '')); rest -= c * u[1]; }
		if (parts.length === 2) break;
	}
	return parts.join(' ');
}

/* Trailing dot handling. Zone data is full of both forms and mixing them up is
   the single most common way to break a zone, so the editor keeps track. */
function isAbsolute(name) { return /\.$/.test(String(name || '')); }

function absName(name, origin) {
	let n = String(name === null || name === undefined ? '' : name).trim();
	const o = String(origin || '.').trim() || '.';
	if (n === '' || n === '@') return o;
	if (isAbsolute(n)) return n;
	return o === '.' ? n + '.' : n + '.' + o;
}

/* Case insensitive comparison key for a name (DNS names are case insensitive). */
function nameKey(name, origin) { return absName(name, origin).toLowerCase(); }

function isInZone(fqdn, origin) {
	const a = String(fqdn || '').toLowerCase();
	const o = String(origin || '').toLowerCase();
	if (o === '.') return true;
	return a === o || a.endsWith('.' + o);
}

function isIPv4(s) {
	if (!/^\d{1,3}(\.\d{1,3}){3}$/.test(String(s || ''))) return false;
	return String(s).split('.').every(function (p) {
		if (p.length > 1 && p[0] === '0') return false;   /* 010 is not 10 in a zone file */
		const n = parseInt(p, 10);
		return n >= 0 && n <= 255;
	});
}

function isIPv6(s) {
	const v = String(s || '');
	if (v === '' || v.length > 45) return false;
	if (!/^[0-9A-Fa-f:.]+$/.test(v)) return false;
	if ((v.match(/::/g) || []).length > 1) return false;
	const halves = v.split('::');
	if (halves.length > 2) return false;
	let groups = 0;
	let sawV4 = false;
	for (let h = 0; h < halves.length; h++) {
		if (halves[h] === '') continue;
		const parts = halves[h].split(':');
		for (let i = 0; i < parts.length; i++) {
			const p = parts[i];
			if (p === '') return false;
			if (p.indexOf('.') > -1) {
				/* An embedded IPv4 tail is only allowed as the very last element. */
				if (h !== halves.length - 1 || i !== parts.length - 1) return false;
				if (!isIPv4(p)) return false;
				sawV4 = true;
				groups += 2;
				continue;
			}
			if (!/^[0-9A-Fa-f]{1,4}$/.test(p)) return false;
			groups += 1;
		}
	}
	if (halves.length === 1) return groups === 8 || (sawV4 && groups === 8);
	return groups <= 7;
}

function isUint(s, bits) {
	if (!/^\d+$/.test(String(s || ''))) return false;
	const n = Number(s);
	return n <= (Math.pow(2, bits) - 1);
}

function isHex(s) { return /^[0-9A-Fa-f\s]+$/.test(String(s || '')) && /[0-9A-Fa-f]/.test(String(s || '')); }

function isBase64(s) { return /^[A-Za-z0-9+/=\s]+$/.test(String(s || '')) && /[A-Za-z0-9]/.test(String(s || '')); }

function isBase32Hex(s) { return /^[0-9A-Va-v=\s]+$/.test(String(s || '')); }

/* A single label of a domain name, escapes counted as one character. */
function labelLength(label) {
	let n = 0;
	for (let i = 0; i < label.length; i++) {
		if (label[i] === '\\') {
			if (/^\\\d{3}/.test(label.slice(i))) { i += 3; } else { i += 1; }
			n++;
			continue;
		}
		n++;
	}
	return n;
}

/* Presentation format name check. Deliberately permissive - anything bind
   accepts has to pass here, the editor only flags what is certainly wrong. */
function checkName(name) {
	const out = [];
	const n = String(name || '').trim();
	if (n === '' || n === '@') return out;
	if (n === '.') return out;
	const body = isAbsolute(n) ? n.slice(0, -1) : n;
	if (body === '') { out.push({ level: 'error', msg: 'Empty name.' }); return out; }
	const labels = body.split('.');
	for (let i = 0; i < labels.length; i++) {
		const l = labels[i];
		if (l === '') {
			out.push({ level: 'error', msg: 'Empty label - the name contains ".." or starts with a dot.' });
			continue;
		}
		if (l === '*') {
			if (i !== 0) out.push({ level: 'error', msg: 'A wildcard "*" is only a wildcard as the leftmost label (RFC 4592).' });
			continue;
		}
		if (labelLength(l) > 63) out.push({ level: 'error', msg: 'Label "' + l + '" is longer than 63 characters.' });
		if (l.indexOf('*') > -1) out.push({ level: 'warn', msg: 'A "*" inside a label is a literal asterisk, not a wildcard.' });
		if (hasUnicode(l)) out.push({ level: 'warn', msg: 'Non-ASCII characters have to be converted to punycode (an A-label) before bind can serve them.' });
		else if (!/^[A-Za-z0-9_\\/+*-]+$/.test(l) && !/^[A-Za-z0-9_]/.test(l)) out.push({ level: 'warn', msg: 'Label "' + l + '" contains unusual characters - escape them as \\DDD if they are intended.' });
	}
	if (body.replace(/\\\d{3}/g, 'x').length > 253) out.push({ level: 'error', msg: 'The name is longer than 253 characters.' });
	return out;
}

/* Quote a value as a zone file character-string. */
function quoteString(s) {
	return '"' + String(s === null || s === undefined ? '' : s).replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"';
}

/* Take a character-string token from the zone and give back its plain value. */
function unquoteString(s) {
	const v = String(s === null || s === undefined ? '' : s);
	if (v.length >= 2 && v[0] === '"' && v[v.length - 1] === '"') {
		return v.slice(1, -1).replace(/\\(.)/g, '$1');
	}
	return v;
}

/* Byte length of a character-string once the escapes are resolved (the 255 byte
   limit of RFC 1035 counts bytes, not characters - relevant for UTF-8 in TXT). */
function stringByteLength(s) {
	try { return new TextEncoder().encode(s).length; } catch (e) { return String(s).length; }
}

/* Split a long value into <= 255 byte character-strings. DKIM keys and long SPF
   records need this, a single string over 255 bytes makes bind refuse the zone. */
function chunkString(value, size) {
	const limit = size || 255;
	const chars = Array.from(String(value || ''));
	const out = [];
	let cur = '', curLen = 0;
	for (const ch of chars) {
		const chLen = stringByteLength(ch) + (ch === '"' || ch === '\\' ? 1 : 0);
		if (curLen + chLen > limit) { out.push(cur); cur = ''; curLen = 0; }
		cur += ch;
		curLen += chLen;
	}
	if (cur !== '' || out.length === 0) out.push(cur);
	return out;
}

/* ##################################################################
	Enumerations used by several record types
################################################################## */
const DNSSEC_ALGS = {
	1: 'RSAMD5 (must not be used)', 3: 'DSA (deprecated)', 5: 'RSASHA1',
	6: 'DSA-NSEC3-SHA1 (deprecated)', 7: 'RSASHA1-NSEC3-SHA1', 8: 'RSASHA256',
	10: 'RSASHA512', 12: 'ECC-GOST (deprecated)', 13: 'ECDSAP256SHA256',
	14: 'ECDSAP384SHA384', 15: 'ED25519', 16: 'ED448'
};
const DS_DIGESTS = { 1: 'SHA-1 (deprecated)', 2: 'SHA-256', 3: 'GOST R 34.11-94', 4: 'SHA-384' };
const SSHFP_ALGS = { 1: 'RSA', 2: 'DSA', 3: 'ECDSA', 4: 'Ed25519', 6: 'Ed448' };
const SSHFP_FPS = { 1: 'SHA-1 (deprecated)', 2: 'SHA-256' };
const TLSA_USAGE = { 0: 'PKIX-TA', 1: 'PKIX-EE', 2: 'DANE-TA', 3: 'DANE-EE' };
const TLSA_SELECTOR = { 0: 'Full certificate', 1: 'Public key (SPKI)' };
const TLSA_MATCHING = { 0: 'Exact match', 1: 'SHA-256', 2: 'SHA-512' };
const CERT_TYPES = { 1: 'PKIX', 2: 'SPKI', 3: 'PGP', 4: 'IPKIX', 5: 'ISPKI', 6: 'IPGP', 7: 'ACPKIX', 8: 'IACPKIX', 253: 'URI', 254: 'OID' };
const CAA_TAGS = ['issue', 'issuewild', 'iodef', 'contactemail', 'contactphone', 'issuemail', 'issuevmc'];
const SVCB_KEYS = ['alpn', 'no-default-alpn', 'port', 'ipv4hint', 'ipv6hint', 'ech', 'mandatory', 'dohpath', 'ohttp'];

/* ##################################################################
	Record type registry
	------------------------------------------------------------------
	Every type of the IANA registry is listed. "fields" drives the
	structured editor and the per field validation; a type without
	fields is edited as raw record data and still round trips. Types
	flagged "meta" only exist on the wire and are an error in a zone
	file, "managed" types are written by bind itself when the zone is
	signed.
################################################################## */
const CAT = {
	common:  { label: 'Common records',         color: '#2563eb' },
	service: { label: 'Service and mail',       color: '#0f766e' },
	dnssec:  { label: 'DNSSEC',                 color: '#7c3aed' },
	crypto:  { label: 'Certificates and keys',  color: '#16a34a' },
	infra:   { label: 'Infrastructure',         color: '#0891b2' },
	legacy:  { label: 'Historic and rare',      color: '#78716c' },
	meta:    { label: 'Protocol only (not valid in a zone file)', color: '#dc2626' }
};

/* Field kinds: uint8 uint16 uint32 ipv4 ipv6 name string text hex base64
   base32hex enum port raw. "rest: true" swallows everything that is left. */
function f(key, label, kind, opts) {
	const o = opts || {};
	o.key = key; o.label = label; o.kind = kind;
	return o;
}

const TYPES = {
	/* ---------- Common ---------- */
	A:     { num: 1,  cat: 'common', rfc: 'RFC 1035', desc: 'IPv4 address of a host.',
		hint: '192.0.2.1', fields: [f('address', 'IPv4 address', 'ipv4', { hint: '192.0.2.1' })] },
	AAAA:  { num: 28, cat: 'common', rfc: 'RFC 3596', desc: 'IPv6 address of a host.',
		hint: '2001:db8::1', fields: [f('address', 'IPv6 address', 'ipv6', { hint: '2001:db8::1' })] },
	CNAME: { num: 5,  cat: 'common', rfc: 'RFC 1035', desc: 'Alias: this name continues at another name. A name with a CNAME may not carry any other record.',
		hint: 'target.example.com.', single: true, fields: [f('target', 'Points to', 'name', { hint: 'target.example.com.' })] },
	MX:    { num: 15, cat: 'common', rfc: 'RFC 1035', desc: 'Mail exchanger for this name. The lowest priority is tried first.',
		hint: '10 mail.example.com.', fields: [
			f('preference', 'Priority', 'uint16', { hint: '10', width: 'narrow' }),
			f('exchange', 'Mail server', 'name', { hint: 'mail.example.com.' })] },
	NS:    { num: 2,  cat: 'common', rfc: 'RFC 1035', desc: 'Authoritative name server for this zone or a delegated child zone.',
		hint: 'ns1.example.com.', fields: [f('nsdname', 'Name server', 'name', { hint: 'ns1.example.com.' })] },
	TXT:   { num: 16, cat: 'common', rfc: 'RFC 1035', desc: 'Free text. Used for SPF, DKIM, DMARC and domain ownership proofs.',
		hint: '"v=spf1 -all"', fields: [f('text', 'Text', 'text', { hint: 'v=spf1 include:example.com ~all', rest: true })] },
	SOA:   { num: 6,  cat: 'common', rfc: 'RFC 1035', desc: 'Start of authority. Exactly one per zone, on the zone apex.',
		hint: 'ns1.example.com. hostmaster.example.com. ( 1 7200 540 604800 3600 )', single: true, fields: [
			f('mname', 'Primary name server', 'name', { hint: 'ns1.example.com.' }),
			f('rname', 'Zone contact (e-mail as a name)', 'name', { hint: 'hostmaster.example.com.' }),
			f('serial', 'Serial', 'uint32', { hint: '2026010101', note: 'Raised automatically when you save.' }),
			f('refresh', 'Refresh', 'ttl', { hint: '7200' }),
			f('retry', 'Retry', 'ttl', { hint: '540' }),
			f('expire', 'Expire', 'ttl', { hint: '604800' }),
			f('minimum', 'Negative cache TTL', 'ttl', { hint: '3600' })] },
	PTR:   { num: 12, cat: 'common', rfc: 'RFC 1035', desc: 'Reverse lookup: this address belongs to that host name.',
		hint: 'host.example.com.', fields: [f('ptrdname', 'Host name', 'name', { hint: 'host.example.com.' })] },
	SRV:   { num: 33, cat: 'common', rfc: 'RFC 2782', desc: 'Where a service lives. The owner name has the form _service._protocol.',
		hint: '10 20 443 target.example.com.', fields: [
			f('priority', 'Priority', 'uint16', { hint: '10', width: 'narrow' }),
			f('weight', 'Weight', 'uint16', { hint: '20', width: 'narrow' }),
			f('port', 'Port', 'port', { hint: '443', width: 'narrow' }),
			f('target', 'Target host', 'name', { hint: 'server.example.com.' })] },
	CAA:   { num: 257, cat: 'common', rfc: 'RFC 8659', desc: 'Which certificate authorities may issue certificates for this domain.',
		hint: '0 issue "letsencrypt.org"', fields: [
			f('flags', 'Flags', 'uint8', { hint: '0', width: 'narrow' }),
			f('tag', 'Tag', 'enum', { values: CAA_TAGS, hint: 'issue', width: 'medium', free: true }),
			f('value', 'Value', 'string', { hint: 'letsencrypt.org' })] },

	/* ---------- Service and mail ---------- */
	HTTPS: { num: 65, cat: 'service', rfc: 'RFC 9460', desc: 'Service binding for HTTPS: alternative endpoint, ALPN, ECH and address hints.',
		hint: '1 . alpn="h3,h2"', fields: [
			f('priority', 'Priority', 'uint16', { hint: '1', width: 'narrow' }),
			f('target', 'Target', 'name', { hint: '.' }),
			f('params', 'Parameters', 'raw', { hint: 'alpn="h3,h2" ipv4hint=192.0.2.1', rest: true, optional: true })] },
	SVCB:  { num: 64, cat: 'service', rfc: 'RFC 9460', desc: 'Generic service binding, the protocol independent form of HTTPS.',
		hint: '1 svc.example.com. port=8443', fields: [
			f('priority', 'Priority', 'uint16', { hint: '1', width: 'narrow' }),
			f('target', 'Target', 'name', { hint: 'svc.example.com.' }),
			f('params', 'Parameters', 'raw', { hint: 'port=8443', rest: true, optional: true })] },
	URI:   { num: 256, cat: 'service', rfc: 'RFC 7553', desc: 'Maps a name to a URI. The owner name has the form _service._protocol.',
		hint: '10 1 "https://example.com/"', fields: [
			f('priority', 'Priority', 'uint16', { hint: '10', width: 'narrow' }),
			f('weight', 'Weight', 'uint16', { hint: '1', width: 'narrow' }),
			f('target', 'URI', 'string', { hint: 'https://example.com/' })] },
	NAPTR: { num: 35, cat: 'service', rfc: 'RFC 3403', desc: 'Rewrite rule used by ENUM and SIP to turn a name into a URI or another name.',
		hint: '100 10 "u" "E2U+sip" "!^.*$!sip:info@example.com!" .', fields: [
			f('order', 'Order', 'uint16', { hint: '100', width: 'narrow' }),
			f('preference', 'Preference', 'uint16', { hint: '10', width: 'narrow' }),
			f('flags', 'Flags', 'string', { hint: 'u', width: 'narrow' }),
			f('service', 'Service', 'string', { hint: 'E2U+sip', width: 'medium' }),
			f('regexp', 'Regexp', 'string', { hint: '!^.*$!sip:info@example.com!' }),
			f('replacement', 'Replacement', 'name', { hint: '.' })] },
	KX:    { num: 36, cat: 'service', rfc: 'RFC 2230', desc: 'Key exchanger for this name, same shape as MX.',
		hint: '10 kx.example.com.', fields: [
			f('preference', 'Preference', 'uint16', { hint: '10', width: 'narrow' }),
			f('exchanger', 'Key exchanger', 'name', { hint: 'kx.example.com.' })] },
	SPF:   { num: 99, cat: 'service', rfc: 'RFC 7208', desc: 'Obsolete SPF record type. Publish SPF policies as a TXT record instead.',
		hint: '"v=spf1 -all"', obsolete: true, fields: [f('text', 'Text', 'text', { rest: true })] },
	DNAME: { num: 39, cat: 'service', rfc: 'RFC 6672', desc: 'Redirects a whole subtree of names to another subtree.',
		hint: 'target.example.com.', single: true, fields: [f('target', 'Points to', 'name', { hint: 'target.example.com.' })] },
	RP:    { num: 17, cat: 'service', rfc: 'RFC 1183', desc: 'Responsible person: a mailbox and a pointer to a TXT record with more information.',
		hint: 'hostmaster.example.com. .', fields: [
			f('mbox', 'Mailbox (as a name)', 'name', { hint: 'hostmaster.example.com.' }),
			f('txtdname', 'TXT record with details', 'name', { hint: '.' })] },
	AFSDB: { num: 18, cat: 'service', rfc: 'RFC 1183', desc: 'AFS cell database or DCE authentication server.',
		hint: '1 afs.example.com.', fields: [
			f('subtype', 'Subtype', 'uint16', { hint: '1', width: 'narrow' }),
			f('hostname', 'Host', 'name', { hint: 'afs.example.com.' })] },

	/* ---------- DNSSEC ---------- */
	DS:     { num: 43, cat: 'dnssec', rfc: 'RFC 4034', desc: 'Delegation signer: the fingerprint of a child zone key, published in the parent.',
		hint: '12345 13 2 <digest>', fields: [
			f('keytag', 'Key tag', 'uint16', { hint: '12345', width: 'narrow' }),
			f('algorithm', 'Algorithm', 'enum', { values: DNSSEC_ALGS, numeric: true, width: 'medium' }),
			f('digesttype', 'Digest type', 'enum', { values: DS_DIGESTS, numeric: true, width: 'medium' }),
			f('digest', 'Digest', 'hex', { rest: true })] },
	CDS:    { num: 59, cat: 'dnssec', rfc: 'RFC 7344', desc: 'The DS record this zone wants its parent to publish.',
		hint: '12345 13 2 <digest>', fields: [
			f('keytag', 'Key tag', 'uint16', { width: 'narrow' }),
			f('algorithm', 'Algorithm', 'enum', { values: DNSSEC_ALGS, numeric: true, width: 'medium' }),
			f('digesttype', 'Digest type', 'enum', { values: DS_DIGESTS, numeric: true, width: 'medium' }),
			f('digest', 'Digest', 'hex', { rest: true })] },
	DNSKEY: { num: 48, cat: 'dnssec', rfc: 'RFC 4034', desc: 'Public key of this zone. Written and rotated by bind while the zone is signed.',
		hint: '257 3 13 <public key>', managed: true, fields: [
			f('flags', 'Flags', 'uint16', { hint: '257', width: 'narrow' }),
			f('protocol', 'Protocol', 'uint8', { hint: '3', width: 'narrow' }),
			f('algorithm', 'Algorithm', 'enum', { values: DNSSEC_ALGS, numeric: true, width: 'medium' }),
			f('publickey', 'Public key', 'base64', { rest: true })] },
	CDNSKEY:{ num: 60, cat: 'dnssec', rfc: 'RFC 7344', desc: 'The DNSKEY this zone wants its parent to pick up.',
		hint: '257 3 13 <public key>', fields: [
			f('flags', 'Flags', 'uint16', { width: 'narrow' }),
			f('protocol', 'Protocol', 'uint8', { width: 'narrow' }),
			f('algorithm', 'Algorithm', 'enum', { values: DNSSEC_ALGS, numeric: true, width: 'medium' }),
			f('publickey', 'Public key', 'base64', { rest: true })] },
	RRSIG:  { num: 46, cat: 'dnssec', rfc: 'RFC 4034', desc: 'Signature over one record set. Generated by bind, never written by hand.',
		managed: true, hint: 'A 13 2 3600 20260101000000 20251201000000 12345 example.com. <signature>' },
	NSEC:   { num: 47, cat: 'dnssec', rfc: 'RFC 4034', desc: 'Proof that a name does not exist. Generated by bind.', managed: true },
	NSEC3:  { num: 50, cat: 'dnssec', rfc: 'RFC 5155', desc: 'Hashed proof of non-existence. Generated by bind.', managed: true },
	NSEC3PARAM: { num: 51, cat: 'dnssec', rfc: 'RFC 5155', desc: 'NSEC3 hashing parameters of this zone.', managed: true },
	CSYNC:  { num: 62, cat: 'dnssec', rfc: 'RFC 7477', desc: 'Asks the parent zone to copy over NS and address records.',
		hint: '66 3 A NS AAAA' },
	ZONEMD: { num: 63, cat: 'dnssec', rfc: 'RFC 8976', desc: 'Message digest over the whole zone, used to verify a zone transfer or file.',
		hint: '2026010101 1 1 <digest>' },
	DLV:    { num: 32769, cat: 'dnssec', rfc: 'RFC 4431', desc: 'Lookaside validation anchor. The public lookaside service was shut down in 2017.',
		obsolete: true },
	TA:     { num: 32768, cat: 'dnssec', rfc: 'draft-ietf-dnsext-trustupdate-timers', desc: 'DNSSEC trust anchor, same shape as DS.' },
	KEY:    { num: 25, cat: 'dnssec', rfc: 'RFC 2535', desc: 'Older key record, today only used for SIG(0) and TKEY.',
		fields: [
			f('flags', 'Flags', 'uint16', { width: 'narrow' }),
			f('protocol', 'Protocol', 'uint8', { width: 'narrow' }),
			f('algorithm', 'Algorithm', 'enum', { values: DNSSEC_ALGS, numeric: true, width: 'medium' }),
			f('publickey', 'Public key', 'base64', { rest: true })] },
	SIG:    { num: 24, cat: 'dnssec', rfc: 'RFC 2535', desc: 'Predecessor of RRSIG.', obsolete: true },
	NXT:    { num: 30, cat: 'dnssec', rfc: 'RFC 2535', desc: 'Predecessor of NSEC.', obsolete: true },

	/* ---------- Certificates and keys ---------- */
	TLSA:   { num: 52, cat: 'crypto', rfc: 'RFC 6698', desc: 'DANE: pins a TLS certificate. The owner name has the form _port._protocol.',
		hint: '3 1 1 <hash>', fields: [
			f('usage', 'Usage', 'enum', { values: TLSA_USAGE, numeric: true, width: 'medium' }),
			f('selector', 'Selector', 'enum', { values: TLSA_SELECTOR, numeric: true, width: 'medium' }),
			f('matchingtype', 'Matching type', 'enum', { values: TLSA_MATCHING, numeric: true, width: 'medium' }),
			f('certificate', 'Certificate association data', 'hex', { rest: true })] },
	SMIMEA: { num: 53, cat: 'crypto', rfc: 'RFC 8162', desc: 'S/MIME certificate association, TLSA for e-mail addresses.',
		hint: '3 1 1 <hash>', fields: [
			f('usage', 'Usage', 'enum', { values: TLSA_USAGE, numeric: true, width: 'medium' }),
			f('selector', 'Selector', 'enum', { values: TLSA_SELECTOR, numeric: true, width: 'medium' }),
			f('matchingtype', 'Matching type', 'enum', { values: TLSA_MATCHING, numeric: true, width: 'medium' }),
			f('certificate', 'Certificate association data', 'hex', { rest: true })] },
	SSHFP:  { num: 44, cat: 'crypto', rfc: 'RFC 4255', desc: 'Fingerprint of an SSH host key, so clients can verify it through DNSSEC.',
		hint: '4 2 <fingerprint>', fields: [
			f('algorithm', 'Key algorithm', 'enum', { values: SSHFP_ALGS, numeric: true, width: 'medium' }),
			f('fptype', 'Fingerprint type', 'enum', { values: SSHFP_FPS, numeric: true, width: 'medium' }),
			f('fingerprint', 'Fingerprint', 'hex', { rest: true })] },
	CERT:   { num: 37, cat: 'crypto', rfc: 'RFC 4398', desc: 'Stores a certificate or a certificate revocation list.',
		hint: '1 12345 13 <certificate>', fields: [
			f('type', 'Certificate type', 'enum', { values: CERT_TYPES, numeric: true, width: 'medium' }),
			f('keytag', 'Key tag', 'uint16', { width: 'narrow' }),
			f('algorithm', 'Algorithm', 'enum', { values: DNSSEC_ALGS, numeric: true, width: 'medium' }),
			f('certificate', 'Certificate', 'base64', { rest: true })] },
	OPENPGPKEY: { num: 61, cat: 'crypto', rfc: 'RFC 7929', desc: 'OpenPGP public key for an e-mail address.',
		hint: '<public key>', fields: [f('publickey', 'Public key', 'base64', { rest: true })] },
	IPSECKEY: { num: 45, cat: 'crypto', rfc: 'RFC 4025', desc: 'IPsec gateway and public key for this name.',
		hint: '10 1 2 192.0.2.38 <public key>' },
	HIP:    { num: 55, cat: 'crypto', rfc: 'RFC 8005', desc: 'Host identity protocol: host identity tag, public key and rendezvous servers.' },
	DHCID:  { num: 49, cat: 'crypto', rfc: 'RFC 4701', desc: 'Ties a name to a DHCP client so two clients cannot overwrite each other.',
		fields: [f('data', 'DHCP identifier', 'base64', { rest: true })] },

	/* ---------- Infrastructure ---------- */
	LOC:    { num: 29, cat: 'infra', rfc: 'RFC 1876', desc: 'Geographic location of a host.',
		hint: '51 30 12.748 N 0 7 39.611 W 0.00m 1m 10000m 10m' },
	HINFO:  { num: 13, cat: 'infra', rfc: 'RFC 1035', desc: 'CPU and operating system of a host. Rarely published, it leaks information.',
		hint: '"AMD64" "LINUX"', fields: [
			f('cpu', 'CPU', 'string', { hint: 'AMD64', width: 'medium' }),
			f('os', 'Operating system', 'string', { hint: 'LINUX' })] },
	APL:    { num: 42, cat: 'infra', rfc: 'RFC 3123', desc: 'A list of address prefixes.', hint: '1:192.0.2.0/24 2:2001:db8::/32' },
	NID:    { num: 104, cat: 'infra', rfc: 'RFC 6742', desc: 'ILNP node identifier.', hint: '10 0014:4fff:ff20:ee64' },
	L32:    { num: 105, cat: 'infra', rfc: 'RFC 6742', desc: 'ILNP 32 bit locator.', hint: '10 10.1.2.0' },
	L64:    { num: 106, cat: 'infra', rfc: 'RFC 6742', desc: 'ILNP 64 bit locator.', hint: '10 2001:0db8:1140:1000' },
	LP:     { num: 107, cat: 'infra', rfc: 'RFC 6742', desc: 'ILNP locator pointer.', hint: '10 l64-subnet.example.com.' },
	EUI48:  { num: 108, cat: 'infra', rfc: 'RFC 7043', desc: '48 bit MAC address.', hint: '00-00-5e-00-53-2a' },
	EUI64:  { num: 109, cat: 'infra', rfc: 'RFC 7043', desc: '64 bit MAC address.', hint: '00-00-5e-ef-10-00-00-2a' },
	AMTRELAY: { num: 260, cat: 'infra', rfc: 'RFC 8777', desc: 'Relay for automatic multicast tunneling.', hint: '10 0 2 2001:db8::1' },
	DSYNC:  { num: 66, cat: 'infra', rfc: 'RFC 9859', desc: 'Where to notify the parent zone about DS changes.', hint: 'CDS 1 5359 ns1.example.com.' },
	RESINFO:{ num: 261, cat: 'infra', rfc: 'RFC 9606', desc: 'Resolver information, published by a resolver about itself.', hint: '"qnamemin exterr=15,16,17"' },
	WALLET: { num: 262, cat: 'infra', rfc: 'none (registered)', desc: 'Public wallet address for a name.' },
	CLA:    { num: 263, cat: 'infra', rfc: 'none (registered)', desc: 'Delay tolerant networking convergence layer adapter.' },
	IPN:    { num: 264, cat: 'infra', rfc: 'none (registered)', desc: 'Delay tolerant networking IPN node number.' },
	WKS:    { num: 11, cat: 'infra', rfc: 'RFC 1035', desc: 'Well known services of a host. Superseded by SRV.', obsolete: true,
		hint: '192.0.2.1 tcp smtp' },
	NSAP:   { num: 22, cat: 'infra', rfc: 'RFC 1706', desc: 'NSAP address of an OSI end system.', obsolete: true },
	'NSAP-PTR': { num: 23, cat: 'infra', rfc: 'RFC 1706', desc: 'Reverse mapping for NSAP addresses.', obsolete: true },
	X25:    { num: 19, cat: 'infra', rfc: 'RFC 1183', desc: 'X.25 PSDN address.', obsolete: true },
	ISDN:   { num: 20, cat: 'infra', rfc: 'RFC 1183', desc: 'ISDN address.', obsolete: true },
	RT:     { num: 21, cat: 'infra', rfc: 'RFC 1183', desc: 'Route through an intermediate host.', obsolete: true,
		fields: [f('preference', 'Preference', 'uint16', { width: 'narrow' }), f('host', 'Intermediate host', 'name')] },
	PX:     { num: 26, cat: 'infra', rfc: 'RFC 2163', desc: 'X.400 to RFC 822 mapping.', obsolete: true },
	GPOS:   { num: 27, cat: 'infra', rfc: 'RFC 1712', desc: 'Predecessor of LOC.', obsolete: true },
	A6:     { num: 38, cat: 'infra', rfc: 'RFC 6563', desc: 'Historic IPv6 address record. Use AAAA.', obsolete: true },
	ATMA:   { num: 34, cat: 'infra', rfc: 'ATM Forum', desc: 'ATM address.', obsolete: true },

	/* ---------- Historic and rare ---------- */
	MB:     { num: 7,  cat: 'legacy', rfc: 'RFC 1035', desc: 'Mailbox host. Experimental, never deployed.', obsolete: true,
		fields: [f('madname', 'Host', 'name')] },
	MG:     { num: 8,  cat: 'legacy', rfc: 'RFC 1035', desc: 'Mail group member.', obsolete: true, fields: [f('mgmname', 'Mailbox', 'name')] },
	MR:     { num: 9,  cat: 'legacy', rfc: 'RFC 1035', desc: 'Mail rename.', obsolete: true, fields: [f('newname', 'New mailbox', 'name')] },
	MINFO:  { num: 14, cat: 'legacy', rfc: 'RFC 1035', desc: 'Mailbox or mail list information.', obsolete: true },
	MD:     { num: 3,  cat: 'legacy', rfc: 'RFC 1035', desc: 'Mail destination. Obsolete since 1987, use MX.', obsolete: true },
	MF:     { num: 4,  cat: 'legacy', rfc: 'RFC 1035', desc: 'Mail forwarder. Obsolete since 1987, use MX.', obsolete: true },
	NULL:   { num: 10, cat: 'legacy', rfc: 'RFC 1035', desc: 'Placeholder type. Cannot appear in a zone file.', meta: true },
	NINFO:  { num: 56, cat: 'legacy', rfc: 'none', desc: 'Zone status information.', obsolete: true },
	RKEY:   { num: 57, cat: 'legacy', rfc: 'none', desc: 'Key for encrypted record data.', obsolete: true },
	TALINK: { num: 58, cat: 'legacy', rfc: 'none', desc: 'Trust anchor link.', obsolete: true },
	SINK:   { num: 40, cat: 'legacy', rfc: 'none', desc: 'Kitchen sink record.', obsolete: true },
	EID:    { num: 31, cat: 'legacy', rfc: 'none', desc: 'Endpoint identifier.', obsolete: true },
	NIMLOC: { num: 32, cat: 'legacy', rfc: 'none', desc: 'Nimrod locator.', obsolete: true },
	AVC:    { num: 258, cat: 'legacy', rfc: 'none', desc: 'Application visibility and control.', obsolete: true },
	DOA:    { num: 259, cat: 'legacy', rfc: 'none', desc: 'Digital object architecture.', obsolete: true },
	UINFO:  { num: 100, cat: 'legacy', rfc: 'none', desc: 'Reserved, never specified.', obsolete: true },
	UID:    { num: 101, cat: 'legacy', rfc: 'none', desc: 'Reserved, never specified.', obsolete: true },
	GID:    { num: 102, cat: 'legacy', rfc: 'none', desc: 'Reserved, never specified.', obsolete: true },
	UNSPEC: { num: 103, cat: 'legacy', rfc: 'none', desc: 'Reserved, never specified.', obsolete: true },

	/* ---------- Protocol only ---------- */
	OPT:    { num: 41,  cat: 'meta', rfc: 'RFC 6891', desc: 'EDNS(0) pseudo record. Exists only on the wire.', meta: true },
	TKEY:   { num: 249, cat: 'meta', rfc: 'RFC 2930', desc: 'Transaction key. Exists only on the wire.', meta: true },
	TSIG:   { num: 250, cat: 'meta', rfc: 'RFC 8945', desc: 'Transaction signature. Exists only on the wire.', meta: true },
	IXFR:   { num: 251, cat: 'meta', rfc: 'RFC 1995', desc: 'Incremental zone transfer. A query type, not zone data.', meta: true },
	AXFR:   { num: 252, cat: 'meta', rfc: 'RFC 1035', desc: 'Full zone transfer. A query type, not zone data.', meta: true },
	MAILB:  { num: 253, cat: 'meta', rfc: 'RFC 1035', desc: 'Query for mailbox records. A query type, not zone data.', meta: true },
	MAILA:  { num: 254, cat: 'meta', rfc: 'RFC 1035', desc: 'Query for mail agent records. A query type, not zone data.', meta: true },
	ANY:    { num: 255, cat: 'meta', rfc: 'RFC 8482', desc: 'Query for all records. A query type, not zone data.', meta: true }
};

/* Types offered in the "add record" dropdown, in the order they are shown. */
const TYPE_ORDER = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV', 'CAA', 'PTR', 'SOA'];

/* Individual colours for the handful of types that carry most of a zone, the
   rest is coloured by category so the table stays readable. */
const TYPE_COLORS = {
	A: '#2563eb', AAAA: '#7c3aed', NS: '#0891b2', MX: '#d97706', CNAME: '#16a34a',
	TXT: '#dc2626', SOA: '#6b7280', PTR: '#be185d', SRV: '#0f766e', CAA: '#9333ea'
};

/* RFC 3597: a type nobody knows is written as TYPE<number>. */
function isGenericType(t) { return /^TYPE\d+$/i.test(String(t || '')); }

function typeInfo(t) {
	const key = String(t || '').toUpperCase();
	if (TYPES[key]) return TYPES[key];
	if (isGenericType(key)) {
		const num = parseInt(key.slice(4), 10);
		return { num: num, cat: 'legacy', rfc: 'RFC 3597', generic: true,
			desc: 'Record type ' + num + ', not known to this editor. Its data is kept exactly as it is.' };
	}
	return null;
}

function typeColor(t) {
	const key = String(t || '').toUpperCase();
	if (TYPE_COLORS[key]) return TYPE_COLORS[key];
	const info = typeInfo(key);
	return info && CAT[info.cat] ? CAT[info.cat].color : '#6b7280';
}

function typeHint(t) {
	const info = typeInfo(t);
	return (info && info.hint) ? info.hint : '';
}

/* ##################################################################
	Scanner
	------------------------------------------------------------------
	One physical line at a time. Quoted strings stay in one piece
	(spaces, semicolons and parentheses inside them are data), the
	comment is cut off at the first ";" outside a string and the
	parenthesis depth is carried over so a record can span lines.
################################################################## */
function scanPhysical(line, depth) {
	const text = String(line === null || line === undefined ? '' : line);
	const tokens = [];
	let comment = null;
	let d = depth || 0;
	let i = 0;
	const n = text.length;
	while (i < n) {
		const ch = text[i];
		if (ch === ' ' || ch === '\t' || ch === '\r') { i++; continue; }
		if (ch === ';') { comment = text.slice(i); break; }
		if (ch === '(') { d++; i++; continue; }
		if (ch === ')') { d--; i++; continue; }
		if (ch === '"') {
			let j = i + 1;
			while (j < n) {
				if (text[j] === '\\') { j += 2; continue; }
				if (text[j] === '"') { j++; break; }
				j++;
			}
			tokens.push({ raw: text.slice(i, Math.min(j, n)), quoted: true });
			i = Math.min(j, n);
			continue;
		}
		let j = i;
		while (j < n) {
			const c = text[j];
			if (c === '\\') { j += 2; continue; }
			if (c === ' ' || c === '\t' || c === '\r' || c === ';' || c === '(' || c === ')' || c === '"') break;
			j++;
		}
		if (j <= i) j = i + 1;
		tokens.push({ raw: text.slice(i, Math.min(j, n)), quoted: false });
		i = Math.min(j, n);
	}
	return { tokens: tokens, comment: comment, depth: d };
}

/* Record data as a token list - used by the structured field editors. */
function splitRdata(rdata) { return scanPhysical(rdata, 0).tokens; }

/* ##################################################################
	Parser
	------------------------------------------------------------------
	Produces a flat list of entries in file order. Records, comment
	lines, blank lines and directives are all entries, which is what
	makes writing the file back out lossless.
################################################################## */
function parseZone(raw, cfg) {
	const conf = cfg || {};
	const zone = {
		origin: conf.origin || '.',
		ttl: null,
		ttlText: null,
		entries: []
	};
	const lines = String(raw === null || raw === undefined ? '' : raw).split(/\r\n|\n|\r/);
	/* The text after the last newline is not a line of its own. */
	if (lines.length && lines[lines.length - 1] === '') lines.pop();

	let lastName = '@';
	let seenRecord = false;
	let consumedOrigin = false;
	let i = 0;

	while (i < lines.length) {
		const line = lines[i];

		if (line.trim() === '') { zone.entries.push({ id: uid(), kind: 'blank', raw: [line] }); i++; continue; }
		if (/^[ \t]*;/.test(line)) { zone.entries.push({ id: uid(), kind: 'comment', raw: [line], text: line.replace(/^[ \t]*/, '') }); i++; continue; }

		/* Collect every physical line of this record - an open parenthesis
		   continues it, which is how SOA records are normally written. */
		const indented = /^[ \t]/.test(line);
		const rawLines = [];
		const tokens = [];
		const comments = [];
		let depth = 0;
		let j = i;
		do {
			const res = scanPhysical(lines[j], depth);
			depth = res.depth;
			rawLines.push(lines[j]);
			for (const t of res.tokens) tokens.push(t);
			if (res.comment) comments.push(res.comment.replace(/^;+\s?/, ''));
			j++;
		} while (depth > 0 && j < lines.length);
		i = j;

		if (tokens.length === 0) {
			/* Nothing but parentheses and a comment. Keep the text as it is. */
			zone.entries.push({ id: uid(), kind: 'comment', raw: rawLines, text: rawLines.join(' ').trim() });
			continue;
		}

		/* ---- Directives ---- */
		if (tokens[0].raw.charAt(0) === '$') {
			const directive = tokens[0].raw.toUpperCase();
			const value = tokens.slice(1).map(t => t.raw).join(' ');
			if (directive === '$ORIGIN') {
				const newOrigin = tokens[1] ? tokens[1].raw : '';
				/* The first $ORIGIN is only swallowed by the header field when it
				   names the apex of this zone. A different origin changes what the
				   names below it mean, so that one stays a line of its own - the
				   save in the backend cuts exactly one leading $ORIGIN line. */
				if (!consumedOrigin && !seenRecord && newOrigin &&
					newOrigin.replace(/\.$/, '').toLowerCase() === String(zone.origin || '').replace(/\.$/, '').toLowerCase()) {
					zone.origin = newOrigin;
					consumedOrigin = true;
					continue;
				}
				if (newOrigin) zone.origin = newOrigin;   /* affects the names that follow */
				zone.entries.push({ id: uid(), kind: 'directive', directive: '$ORIGIN', value: value, raw: rawLines });
				continue;
			}
			if (directive === '$TTL') {
				const parsed = parseTTL(tokens[1] ? tokens[1].raw : '');
				if (!seenRecord && zone.ttl === null && parsed !== null) {
					zone.ttl = parsed;
					zone.ttlText = tokens[1].raw;
					continue;                                  /* shown in the header instead */
				}
				zone.entries.push({ id: uid(), kind: 'directive', directive: '$TTL', value: value, raw: rawLines });
				continue;
			}
			zone.entries.push({ id: uid(), kind: 'directive', directive: directive, value: value, raw: rawLines });
			continue;
		}

		/* ---- Records ---- */
		let k = 0;
		let name;
		if (indented) {
			name = lastName;
		} else {
			name = tokens[0].raw;
			k = 1;
		}

		let ttlText = null;
		let cls = null;
		for (let guard = 0; guard < 2 && k < tokens.length; guard++) {
			const tok = tokens[k];
			if (!tok.quoted && ttlText === null && isTTLToken(tok.raw)) { ttlText = tok.raw; k++; continue; }
			if (!tok.quoted && cls === null && /^(IN|CS|CH|HS)$/i.test(tok.raw)) { cls = tok.raw.toUpperCase(); k++; continue; }
			break;
		}

		const typeTok = tokens[k];
		const type = typeTok && !typeTok.quoted ? typeTok.raw.toUpperCase() : '';
		if (!type || !/^[A-Z][A-Z0-9-]*$/.test(type)) {
			/* Not something this parser understands. It is kept verbatim and shown
			   as an unparsed line - dropping it would delete DNS data on save. */
			zone.entries.push({ id: uid(), kind: 'raw', raw: rawLines, text: rawLines.join('\n') });
			continue;
		}

		if (!indented) lastName = name;
		seenRecord = true;
		zone.entries.push({
			id: uid(),
			kind: 'record',
			raw: rawLines,
			dirty: false,
			name: name,
			ttlText: ttlText,
			ttl: ttlText === null ? null : parseTTL(ttlText),
			cls: cls,
			type: type,
			rdata: tokens.slice(k + 1).map(t => t.raw).join(' '),
			comment: comments.length ? comments.join(' ') : null
		});
	}

	/* No $TTL in the file: bind falls back to the SOA minimum, so the editor
	   writes exactly that value out and nothing about the zone changes. */
	if (zone.ttl === null) {
		const soa = zone.entries.find(e => e.kind === 'record' && e.type === 'SOA');
		let fallback = null;
		if (soa) {
			const vals = rdataToFields('SOA', soa.rdata);
			if (vals) fallback = parseTTL(vals.minimum);
		}
		if (fallback === null) fallback = parseTTL(conf.ttl) === null ? 3600 : parseTTL(conf.ttl);
		zone.ttl = fallback;
		zone.ttlText = formatTTL(fallback);
	}
	return zone;
}

/* ##################################################################
	Serializer
################################################################## */
function padTo(text, width) {
	const s = String(text === null || text === undefined ? '' : text);
	if (s.length >= width) return s + ' ';
	return s + ' '.repeat(width - s.length);
}

function renderRecord(rec) {
	const name = (rec.name === '' || rec.name === null || rec.name === undefined) ? '@' : rec.name;
	const cls = rec.cls || 'IN';
	const ttl = rec.ttlText === null || rec.ttlText === undefined ? '' : String(rec.ttlText);
	const comment = rec.comment ? '\t; ' + rec.comment : '';

	/* The SOA gets the classic block layout. It is the one record people read
	   field by field, and the serial has to stay the first value after the
	   opening parenthesis - that is what the serial bump in the backend looks for. */
	if (rec.type === 'SOA') {
		const v = rdataToFields('SOA', rec.rdata);
		if (v) {
			const pad = '\t\t\t\t';
			return padTo(name, 24) + padTo(ttl, 8) + padTo(cls, 4) + padTo('SOA', 8) +
				v.mname + ' ' + v.rname + ' (' + comment + '\n' +
				pad + padTo(v.serial, 12) + '; serial\n' +
				pad + padTo(v.refresh, 12) + '; refresh\n' +
				pad + padTo(v.retry, 12) + '; retry\n' +
				pad + padTo(v.expire, 12) + '; expire\n' +
				pad + padTo(v.minimum + ' )', 12) + '; negative cache TTL';
		}
	}
	return (padTo(name, 24) + padTo(ttl, 8) + padTo(cls, 4) + padTo(rec.type, 8) + rec.rdata).replace(/\s+$/, '') + comment;
}

function renderEntry(entry) {
	if (entry.kind === 'record') return renderRecord(entry);
	if (entry.kind === 'comment') return entry.text ? (/^\s*;/.test(entry.text) ? entry.text : '; ' + entry.text) : ';';
	if (entry.kind === 'directive') return (entry.directive + ' ' + (entry.value || '')).replace(/\s+$/, '');
	if (entry.kind === 'blank') return '';
	return entry.text || '';
}

/* Zone text for storage. Untouched entries keep their original bytes, only
   what the user changed is rendered again. */
function serializeZone(zone) {
	const out = [];
	/* The backend removes this first line again - the origin of the zone comes
	   from the zone stanza in named.conf. It is written so the editor and the
	   raw view show a complete, valid zone file. */
	if (zone.origin) out.push('$ORIGIN ' + zone.origin);
	out.push('$TTL ' + (zone.ttlText || formatTTL(zone.ttl)));
	for (const e of zone.entries) {
		if (!e.dirty && e.raw && e.raw.length) { for (const l of e.raw) out.push(l); continue; }
		const text = renderEntry(e);
		for (const l of String(text).split('\n')) out.push(l);
	}
	return out.join('\n') + '\n';
}

/* ##################################################################
	Structured record data
	------------------------------------------------------------------
	rdata <-> named fields, driven by the schema in the type registry.
	A record whose data does not fit its schema (extra values, a format
	this editor does not know) simply has no structured view and is
	edited as raw record data. It is never rewritten.
################################################################## */
function rdataToFields(type, rdata) {
	const info = typeInfo(type);
	if (!info || !info.fields) return null;
	const toks = splitRdata(rdata);
	const vals = {};
	let i = 0;
	for (const fld of info.fields) {
		if (fld.rest) {
			const rest = toks.slice(i);
			if (!rest.length && !fld.optional) return null;
			vals[fld.key] = rest.map(t => t.raw).join(' ');
			i = toks.length;
			continue;
		}
		if (i >= toks.length) {
			if (fld.optional) { vals[fld.key] = ''; continue; }
			return null;
		}
		vals[fld.key] = toks[i].raw;
		i++;
	}
	if (i < toks.length) return null;
	return vals;
}

function fieldsToRdata(type, vals) {
	const info = typeInfo(type);
	if (!info || !info.fields) return '';
	const parts = [];
	for (const fld of info.fields) {
		const v = String(vals[fld.key] === undefined || vals[fld.key] === null ? '' : vals[fld.key]).trim();
		if (v === '') continue;
		parts.push(v);
	}
	return parts.join(' ');
}

/* What the user sees in a structured field: quoted strings without their
   quotes, TXT data as one continuous text. */
function fieldToDisplay(fld, rawVal) {
	const v = String(rawVal === undefined || rawVal === null ? '' : rawVal);
	if (fld.kind === 'string') return unquoteString(v.trim());
	if (fld.kind === 'text') {
		const toks = splitRdata(v);
		if (!toks.length) return '';
		return toks.map(t => unquoteString(t.raw)).join('');
	}
	return v;
}

/* ... and back into zone notation. */
function displayToField(fld, display) {
	const v = String(display === undefined || display === null ? '' : display);
	if (fld.kind === 'string') return v === '' ? '' : quoteString(v);
	if (fld.kind === 'text') {
		if (v === '') return '""';
		return chunkString(v, 255).map(quoteString).join(' ');
	}
	if (fld.kind === 'name') return nameToASCII(v.trim());
	return v.trim();
}

/* ##################################################################
	Validation - single field
################################################################## */
function validateField(fld, rawVal, ctx) {
	const out = [];
	const v = String(rawVal === undefined || rawVal === null ? '' : rawVal).trim();
	const label = fld.label;
	if (v === '') {
		if (!fld.optional) out.push({ level: 'error', msg: label + ' is missing.' });
		return out;
	}
	switch (fld.kind) {
		case 'ipv4':
			if (!isIPv4(v)) out.push({ level: 'error', msg: '"' + v + '" is not a valid IPv4 address - four numbers from 0 to 255, for example 192.0.2.1.' });
			else if (/^0\./.test(v)) out.push({ level: 'warn', msg: 'Addresses in 0.0.0.0/8 are not routable.' });
			else if (/^127\./.test(v)) out.push({ level: 'warn', msg: 'A loopback address only ever points back to the client that asked.' });
			else if (/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.|169\.254\.)/.test(v)) out.push({ level: 'info', msg: 'This is a private address (RFC 1918). It only works inside the network it belongs to.' });
			break;
		case 'ipv6':
			if (!isIPv6(v)) out.push({ level: 'error', msg: '"' + v + '" is not a valid IPv6 address, for example 2001:db8::1.' });
			else if (v === '::1') out.push({ level: 'warn', msg: 'A loopback address only ever points back to the client that asked.' });
			else if (/^([fF][cCdD])/.test(v)) out.push({ level: 'info', msg: 'This is a unique local address (fc00::/7), it is not reachable from the internet.' });
			else if (/^([fF][eE]8)/.test(v)) out.push({ level: 'warn', msg: 'Link-local addresses (fe80::/10) cannot be used in DNS in any useful way.' });
			break;
		case 'uint8':
			if (!isUint(v, 8)) out.push({ level: 'error', msg: label + ' has to be a number from 0 to 255.' });
			break;
		case 'uint16':
			if (!isUint(v, 16)) out.push({ level: 'error', msg: label + ' has to be a number from 0 to 65535.' });
			break;
		case 'uint32':
			if (!isUint(v, 32)) out.push({ level: 'error', msg: label + ' has to be a number from 0 to 4294967295.' });
			break;
		case 'port':
			if (!isUint(v, 16) || Number(v) === 0) out.push({ level: 'error', msg: label + ' has to be a port number from 1 to 65535.' });
			break;
		case 'ttl': {
			const t = parseTTL(v);
			if (t === null) out.push({ level: 'error', msg: label + ' has to be a number of seconds or a time like 1h, 2d, 1w.' });
			else if (t > 2147483647) out.push({ level: 'error', msg: label + ' is larger than the protocol allows (2147483647).' });
			break;
		}
		case 'name': {
			if (hasUnicode(v)) {
				out.push({ level: 'warn', msg: 'Contains non-ASCII characters. They are converted to punycode (' + nameToASCII(v) + ') when you leave the field.' });
				break;
			}
			for (const issue of checkName(v)) out.push(issue);
			if (isIPv4(v) || (v.indexOf(':') > -1 && isIPv6(v))) {
				out.push({ level: 'error', msg: label + ' has to be a host name, not an IP address. Point it at a name that has an A or AAAA record.' });
				break;
			}
			if (v !== '@' && v !== '.' && !isAbsolute(v) && v.indexOf('.') > -1) {
				out.push({ level: 'warn', msg: 'No trailing dot: this name is relative and becomes "' + absName(v, ctx && ctx.origin) + '". Add a dot at the end if you meant the name as it is.' });
			}
			break;
		}
		case 'string': {
			const plain = unquoteString(v);
			const bytes = stringByteLength(plain);
			if (bytes > 255) out.push({ level: 'error', msg: label + ' is ' + bytes + ' bytes long, a single character-string may hold 255.' });
			if (v.charAt(0) === '"' && v.charAt(v.length - 1) !== '"') out.push({ level: 'error', msg: label + ' has an opening quote but no closing one.' });
			break;
		}
		case 'text': {
			const toks = splitRdata(v);
			for (const t of toks) {
				const bytes = stringByteLength(unquoteString(t.raw));
				if (bytes > 255) out.push({ level: 'error', msg: 'One of the strings is ' + bytes + ' bytes long. Split the value into pieces of at most 255 bytes - the editor does that for you when you use the text field.' });
			}
			break;
		}
		case 'hex': {
			const clean = v.replace(/\s+/g, '');
			if (!isHex(clean)) out.push({ level: 'error', msg: label + ' has to be hexadecimal.' });
			else if (clean.length % 2 !== 0) out.push({ level: 'error', msg: label + ' has an odd number of hex digits.' });
			break;
		}
		case 'base64':
			if (!isBase64(v)) out.push({ level: 'error', msg: label + ' has to be base64 encoded.' });
			break;
		case 'base32hex':
			if (v !== '-' && !isBase32Hex(v)) out.push({ level: 'error', msg: label + ' has to be base32hex encoded.' });
			break;
		case 'enum': {
			if (fld.numeric) {
				if (!/^\d+$/.test(v)) { out.push({ level: 'error', msg: label + ' has to be a number.' }); break; }
				if (!fld.values[Number(v)]) out.push({ level: 'warn', msg: label + ' ' + v + ' is not one of the values assigned by IANA.' });
				else if (/deprecated|must not/i.test(fld.values[Number(v)])) out.push({ level: 'warn', msg: label + ' ' + v + ' (' + fld.values[Number(v)] + ') should no longer be used.' });
			} else {
				const plain = unquoteString(v).toLowerCase();
				if (fld.values.indexOf(plain) < 0) out.push({ level: 'warn', msg: '"' + plain + '" is not one of the known values (' + fld.values.join(', ') + ').' });
			}
			break;
		}
	}
	return out;
}

/* ##################################################################
	Validation - one record
################################################################## */
function validateRecord(rec, ctx) {
	const out = [];
	const info = typeInfo(rec.type);

	for (const issue of checkName(rec.name)) out.push({ level: issue.level, msg: 'Name: ' + issue.msg });
	if (hasUnicode(rec.name)) out.push({ level: 'warn', msg: 'The name contains non-ASCII characters. Convert it to punycode (' + nameToASCII(rec.name) + ') - bind serves A-labels.' });
	if (/^(IN|CH|HS|CS)$/i.test(String(rec.name).trim())) out.push({ level: 'warn', msg: 'The name is a class name. A record that should inherit the previous name has to start with a space or tab, otherwise the first word is read as the name.' });

	if (rec.ttlText !== null && rec.ttlText !== undefined) {
		const t = parseTTL(rec.ttlText);
		if (t === null) out.push({ level: 'error', msg: 'TTL "' + rec.ttlText + '" is not a time value.' });
		else if (t > 2147483647) out.push({ level: 'error', msg: 'The TTL is larger than the protocol allows (2147483647).' });
		else if (t === 0) out.push({ level: 'info', msg: 'A TTL of 0 forbids caching. That multiplies the query load on this server.' });
		else if (t < 60) out.push({ level: 'info', msg: 'A TTL below one minute is only useful shortly before a planned change.' });
	}

	if (rec.cls && rec.cls !== 'IN') out.push({ level: 'warn', msg: 'Class ' + rec.cls + ' is not the internet class. Almost every resolver only ever asks for IN.' });

	if (!info) {
		out.push({ level: 'warn', msg: 'Record type "' + rec.type + '" is unknown to this editor. It is stored unchanged - write it as TYPE<number> with data in the "\\# length hex" form if bind refuses it (RFC 3597).' });
	} else {
		if (info.meta) out.push({ level: 'error', msg: rec.type + ' only exists inside DNS messages and is not valid in a zone file. bind will refuse the zone.' });
		if (info.managed) out.push({ level: 'warn', msg: rec.type + ' records are generated and rotated by bind itself while the zone is signed. A copy written by hand is at best ignored and at worst breaks validation.' });
		if (info.obsolete) out.push({ level: 'info', msg: rec.type + ' is obsolete. ' + info.desc });
	}

	if (String(rec.rdata || '').trim() === '') {
		out.push({ level: 'error', msg: 'The record has no data.' });
		return out;
	}

	/* Generic (RFC 3597) data is accepted for every type. */
	if (/^\\#\s/.test(String(rec.rdata).trim())) {
		const m = String(rec.rdata).trim().match(/^\\#\s+(\d+)\s*([0-9A-Fa-f\s]*)$/);
		if (!m) out.push({ level: 'error', msg: 'Generic record data has to be written as "\\# <length> <hex>" (RFC 3597).' });
		else if (m[2].replace(/\s+/g, '').length !== Number(m[1]) * 2) out.push({ level: 'error', msg: 'The generic record data says ' + m[1] + ' bytes but carries ' + (m[2].replace(/\s+/g, '').length / 2) + '.' });
		return out;
	}

	if (info && info.fields) {
		const vals = rdataToFields(rec.type, rec.rdata);
		if (!vals) {
			const want = info.fields.length;
			out.push({ level: 'warn', msg: 'The data does not match the ' + want + ' value' + (want !== 1 ? 's' : '') + ' a ' + rec.type + ' record is made of (' + info.fields.map(x => x.label).join(', ') + '). It is kept as it is - check it against ' + info.rfc + '.' });
		} else {
			for (const fld of info.fields) {
				for (const issue of validateField(fld, vals[fld.key], ctx)) {
					out.push({ level: issue.level, msg: issue.msg, field: fld.key });
				}
			}
			for (const issue of validateTypeSpecific(rec, vals, ctx)) out.push(issue);
		}
	}
	return out;
}

/* Checks that need more than one field, or knowledge about how a type is used. */
function validateTypeSpecific(rec, v, ctx) {
	const out = [];
	const origin = ctx && ctx.origin ? ctx.origin : '.';
	const owner = absName(rec.name, origin);

	switch (rec.type) {
		case 'SOA':
			if (String(v.rname).indexOf('@') > -1) out.push({ level: 'error', msg: 'The zone contact is written as a domain name, not as an e-mail address: hostmaster@' + String(origin).replace(/\.$/, '') + ' becomes hostmaster.' + origin });
			if (parseTTL(v.retry) !== null && parseTTL(v.refresh) !== null && parseTTL(v.retry) >= parseTTL(v.refresh)) out.push({ level: 'warn', msg: 'Retry should be shorter than refresh (RFC 1912).' });
			if (parseTTL(v.expire) !== null && parseTTL(v.refresh) !== null && parseTTL(v.expire) < parseTTL(v.refresh) * 2) out.push({ level: 'warn', msg: 'Expire should be a lot longer than refresh, otherwise a secondary drops the zone during a short outage of this server (RFC 1912).' });
			if (parseTTL(v.expire) !== null && parseTTL(v.expire) < 604800) out.push({ level: 'info', msg: 'An expire below one week is short. Secondaries stop answering for this zone once it passes.' });
			if (absName(v.mname, origin).toLowerCase() !== origin.toLowerCase() && !isAbsolute(v.mname) && v.mname.indexOf('.') < 0) out.push({ level: 'info', msg: 'The primary name server is written relative and becomes ' + absName(v.mname, origin) + '.' });
			break;
		case 'CNAME':
		case 'DNAME':
			if (absName(v.target, origin).toLowerCase() === owner.toLowerCase()) out.push({ level: 'error', msg: 'The record points at itself, which is a loop.' });
			break;
		case 'MX':
			if (v.exchange === '.') out.push({ level: 'info', msg: 'An MX of "." says this domain accepts no mail at all (RFC 7505).' });
			else if (Number(v.preference) > 65535) out.push({ level: 'error', msg: 'The priority is out of range.' });
			break;
		case 'SRV':
			if (!/^_[^.]+\._[^.]+/.test(String(rec.name)) && rec.name !== '@') out.push({ level: 'warn', msg: 'An SRV record is only found if its name has the form _service._protocol, for example _sip._tcp.' });
			if (v.target === '.') out.push({ level: 'info', msg: 'A target of "." says this service is not available in this domain.' });
			break;
		case 'TLSA':
		case 'SMIMEA':
			if (!/^_[^.]+\._[^.]+/.test(String(rec.name))) out.push({ level: 'warn', msg: 'The name of a ' + rec.type + ' record has the form _port._protocol, for example _443._tcp.' });
			break;
		case 'CAA': {
			const tag = unquoteString(v.tag).toLowerCase();
			const val = unquoteString(v.value);
			if (Number(v.flags) !== 0 && Number(v.flags) !== 128) out.push({ level: 'warn', msg: 'Only flag 0 and the critical flag 128 are defined.' });
			if ((tag === 'issue' || tag === 'issuewild') && val !== ';' && !/^[A-Za-z0-9.-]+/.test(val)) out.push({ level: 'warn', msg: 'The value of an ' + tag + ' tag is the domain of the certificate authority, or ";" to forbid issuance entirely.' });
			if (tag === 'iodef' && !/^(mailto:|https?:)/i.test(val)) out.push({ level: 'warn', msg: 'An iodef value is a mailto: or https: URL.' });
			break;
		}
		case 'TXT': {
			const text = fieldToDisplay({ kind: 'text' }, v.text);
			if (/^v=spf1/i.test(text)) {
				if (!/(all|redirect=)/i.test(text)) out.push({ level: 'warn', msg: 'This SPF policy has no "all" mechanism and no redirect, so it says nothing about senders it does not list.' });
				if (text.length > 450) out.push({ level: 'info', msg: 'Long SPF policies risk running into the limit of ten DNS lookups on the receiving side.' });
				if (/\+all/i.test(text)) out.push({ level: 'warn', msg: '"+all" lets the whole internet send mail as this domain.' });
			}
			if (/^v=DMARC1/i.test(text)) {
				if (!/^_dmarc(\.|$)/i.test(String(rec.name)) && rec.name !== '_dmarc') out.push({ level: 'error', msg: 'A DMARC policy is only read at the name _dmarc.' });
				if (!/\bp=(none|quarantine|reject)\b/i.test(text)) out.push({ level: 'error', msg: 'A DMARC policy needs a p= tag (none, quarantine or reject).' });
			}
			if (/^v=DKIM1/i.test(text) && String(rec.name).indexOf('_domainkey') < 0) out.push({ level: 'warn', msg: 'A DKIM key is looked up at <selector>._domainkey.' });
			break;
		}
		case 'NS':
			if (v.nsdname === '.') out.push({ level: 'error', msg: 'The root is not a valid name server for a zone.' });
			break;
		case 'HTTPS':
		case 'SVCB': {
			const params = String(v.params || '');
			const re = /(^|\s)([a-z0-9-]+)(=|\s|$)/gi;
			let m;
			while ((m = re.exec(params)) !== null) {
				const key = m[2].toLowerCase();
				if (SVCB_KEYS.indexOf(key) < 0 && !/^key\d+$/.test(key)) out.push({ level: 'warn', msg: 'Service parameter "' + key + '" is not one of the defined keys. Unknown keys are written as key<number> (RFC 9460).' });
			}
			if (Number(v.priority) === 0 && params.trim() !== '') out.push({ level: 'error', msg: 'Priority 0 marks an alias form, which carries no service parameters.' });
			break;
		}
		case 'DS':
		case 'CDS':
			if (Number(v.digesttype) === 2 && v.digest.replace(/\s+/g, '').length !== 64) out.push({ level: 'error', msg: 'A SHA-256 digest is 64 hex characters long, this one has ' + v.digest.replace(/\s+/g, '').length + '.' });
			if (Number(v.digesttype) === 4 && v.digest.replace(/\s+/g, '').length !== 96) out.push({ level: 'error', msg: 'A SHA-384 digest is 96 hex characters long, this one has ' + v.digest.replace(/\s+/g, '').length + '.' });
			break;
		case 'SSHFP':
			if (Number(v.fptype) === 2 && v.fingerprint.replace(/\s+/g, '').length !== 64) out.push({ level: 'error', msg: 'A SHA-256 fingerprint is 64 hex characters long.' });
			break;
		case 'DNSKEY':
		case 'CDNSKEY':
			if (Number(v.protocol) !== 3) out.push({ level: 'error', msg: 'The protocol field of a DNSKEY is always 3.' });
			if (Number(v.flags) !== 256 && Number(v.flags) !== 257 && Number(v.flags) !== 0) out.push({ level: 'warn', msg: 'Usual flag values are 256 (zone signing key) and 257 (key signing key).' });
			break;
	}
	return out;
}

/* ##################################################################
	Validation - the zone as a whole
	------------------------------------------------------------------
	This is where the rules live that no single record can see on its
	own, and they are the ones that actually take a domain down.
################################################################## */
function analyzeZone(zone) {
	const origin = zone.origin || '.';
	const ctx = { origin: origin };
	const perRecord = {};
	const zoneIssues = [];
	const records = zone.entries.filter(e => e.kind === 'record');

	const byName = new Map();       /* name -> records */
	const byNameType = new Map();   /* name|TYPE -> records */
	const push = (map, key, rec) => { if (!map.has(key)) map.set(key, []); map.get(key).push(rec); };

	for (const rec of records) {
		perRecord[rec.id] = validateRecord(rec, ctx);
		const key = nameKey(rec.name, origin);
		push(byName, key, rec);
		push(byNameType, key + '|' + rec.type, rec);
	}

	const add = (rec, level, msg) => { (perRecord[rec.id] = perRecord[rec.id] || []).push({ level: level, msg: msg }); };
	const originKey = String(origin).toLowerCase();

	/* ---- Start of authority ---- */
	const soas = records.filter(r => r.type === 'SOA');
	if (soas.length === 0) zoneIssues.push({ level: 'error', msg: 'The zone has no SOA record. bind refuses a zone without one, so the domain will not answer at all.' });
	if (soas.length > 1) { zoneIssues.push({ level: 'error', msg: 'The zone has ' + soas.length + ' SOA records. There has to be exactly one.' }); soas.forEach(r => add(r, 'error', 'More than one SOA record in this zone.')); }
	for (const r of soas) {
		if (nameKey(r.name, origin) !== originKey) add(r, 'error', 'The SOA record has to sit on the zone apex ("@" or ' + origin + '), not on ' + absName(r.name, origin) + '.');
	}

	/* ---- Name servers ---- */
	const apexNS = records.filter(r => r.type === 'NS' && nameKey(r.name, origin) === originKey);
	if (apexNS.length === 0) zoneIssues.push({ level: 'error', msg: 'The zone has no NS record on its apex. Every zone has to name its own authoritative servers.' });
	else if (apexNS.length === 1) zoneIssues.push({ level: 'info', msg: 'Only one name server is published for this zone. A second one on a different machine keeps the domain resolvable while the first is down.' });

	/* ---- CNAME rules (RFC 1034 / RFC 2181) ---- */
	const dnssecSide = { RRSIG: 1, NSEC: 1, NSEC3: 1, NSEC3PARAM: 1, KEY: 1 };
	for (const [key, list] of byName) {
		const cnames = list.filter(r => r.type === 'CNAME');
		if (!cnames.length) continue;
		const others = list.filter(r => r.type !== 'CNAME' && !dnssecSide[r.type]);
		if (others.length) {
			for (const c of cnames) add(c, 'error', 'A name with a CNAME may not carry any other record, but ' + absName(c.name, origin) + ' also has ' + others.map(o => o.type).filter((x, i, a) => a.indexOf(x) === i).join(', ') + '. bind refuses the zone.');
			for (const o of others) add(o, 'error', 'This record shares its name with a CNAME. A CNAME has to be alone on its name.');
		}
		if (cnames.length > 1) for (const c of cnames) add(c, 'error', 'A name can only have one CNAME.');
		if (key === originKey) for (const c of cnames) add(c, 'error', 'A CNAME on the zone apex collides with the SOA and NS records that have to be there. Use an A/AAAA record, or the HTTPS record type.');
	}

	/* ---- Record sets ---- */
	for (const [key, list] of byNameType) {
		if (list.length < 2) continue;
		const ttls = new Set(list.map(r => (r.ttlText === null || r.ttlText === undefined) ? 'inherit' : String(parseTTL(r.ttlText))));
		if (ttls.size > 1) {
			for (const r of list) add(r, 'warn', 'The records of one set (same name, same type) have to share one TTL (RFC 2181). bind uses the first one and warns about the rest.');
		}
		const seen = new Map();
		for (const r of list) {
			const norm = splitRdata(r.rdata).map(t => t.raw.toLowerCase()).join(' ');
			if (seen.has(norm)) add(r, 'warn', 'This record is an exact duplicate of another one and is dropped when the zone is loaded.');
			else seen.set(norm, r);
		}
		if (key.endsWith('|SOA')) continue;
	}

	/* ---- Delegations, glue and occlusion ---- */
	const delegations = [];
	for (const [key, list] of byName) {
		if (key === originKey) continue;
		if (list.some(r => r.type === 'NS')) delegations.push(key);
	}
	const dnames = records.filter(r => r.type === 'DNAME').map(r => nameKey(r.name, origin));

	/* Every name a NS record points at. An address record below a delegation is
	   glue when it belongs to one of these, and dead weight when it does not. */
	const nsTargets = new Set();
	for (const r of records) {
		if (r.type !== 'NS') continue;
		const v = rdataToFields('NS', r.rdata);
		if (v && v.nsdname) nsTargets.add(nameKey(v.nsdname, origin));
	}

	for (const rec of records) {
		const key = nameKey(rec.name, origin);

		/* Data that does not belong to this zone at all. */
		if (!isInZone(key, originKey)) {
			add(rec, 'error', absName(rec.name, origin) + ' is not inside ' + origin + '. bind ignores out-of-zone data completely.');
			continue;
		}
		/* Below a delegation only glue survives. */
		for (const d of delegations) {
			if (key === d || !key.endsWith('.' + d)) continue;
			if (rec.type === 'A' || rec.type === 'AAAA') {
				if (!nsTargets.has(key)) add(rec, 'warn', 'This name is below the delegation ' + d + ' and no NS record points at it, so it is not glue. The child zone answers for this name, this record is never served.');
			} else {
				add(rec, 'warn', 'This name is below the delegation ' + d + '. Only address records that a name server of that delegation needs (glue) are served from here, everything else is ignored.');
			}
			break;
		}
		for (const d of dnames) {
			if (key !== d && key.endsWith('.' + d)) { add(rec, 'warn', 'This name is below the DNAME at ' + d + ' and is hidden by it.'); break; }
		}
	}

	/* Targets that point at a CNAME - allowed by the parser, broken in practice. */
	const cnameNames = new Set(records.filter(r => r.type === 'CNAME').map(r => nameKey(r.name, origin)));
	const addressNames = new Set(records.filter(r => r.type === 'A' || r.type === 'AAAA').map(r => nameKey(r.name, origin)));
	const targetFields = { MX: 'exchange', NS: 'nsdname', SRV: 'target', PTR: 'ptrdname', KX: 'exchanger', CNAME: 'target', DNAME: 'target', AFSDB: 'hostname', RP: null, LP: null };
	for (const rec of records) {
		if (!(rec.type in targetFields) || targetFields[rec.type] === null) continue;
		const vals = rdataToFields(rec.type, rec.rdata);
		if (!vals) continue;
		const target = vals[targetFields[rec.type]];
		if (!target || target === '.') continue;
		const tkey = nameKey(target, origin);
		if (cnameNames.has(tkey) && rec.type !== 'CNAME') {
			add(rec, 'warn', 'The target ' + absName(target, origin) + ' is a CNAME. An MX, NS, SRV or PTR record has to point at a name that carries addresses directly (RFC 2181).');
		}
		if ((rec.type === 'MX' || rec.type === 'NS' || rec.type === 'SRV') && isInZone(tkey, originKey) && !addressNames.has(tkey) && !cnameNames.has(tkey)) {
			add(rec, 'warn', 'The target ' + absName(target, origin) + ' is inside this zone but has no A or AAAA record here' + (rec.type === 'NS' ? ' - a delegation without glue cannot be followed' : '') + '.');
		}
	}

	/* ---- Mail policy records ---- */
	for (const [key, list] of byName) {
		const spf = list.filter(r => r.type === 'TXT' && /^\s*"?v=spf1/i.test(String(r.rdata)));
		if (spf.length > 1) for (const r of spf) add(r, 'error', 'A name may publish only one SPF policy. Several v=spf1 records make every check fail (RFC 7208).');
		const dmarc = list.filter(r => r.type === 'TXT' && /^\s*"?v=DMARC1/i.test(String(r.rdata)));
		if (dmarc.length > 1) for (const r of dmarc) add(r, 'error', 'A name may publish only one DMARC policy.');
	}

	/* ---- Reverse zones ---- */
	if (/\.(in-addr|ip6)\.arpa\.?$/i.test(origin)) {
		for (const rec of records) {
			if (['PTR', 'NS', 'SOA', 'CNAME', 'TXT', 'DNAME', 'RRSIG', 'NSEC', 'NSEC3', 'NSEC3PARAM', 'DNSKEY'].indexOf(rec.type) < 0) {
				add(rec, 'warn', 'This is a reverse zone. Apart from PTR (and the records that carry the zone itself) nothing here is ever asked for.');
			}
		}
	}

	/* ---- Directives ---- */
	for (const e of zone.entries) {
		if (e.kind !== 'directive') continue;
		if (e.directive === '$INCLUDE') zoneIssues.push({ level: 'error', msg: '$INCLUDE is removed when the zone is saved - it would make bind read files from this server. Paste the contents of the included file into the zone instead.' });
		if (e.directive === '$GENERATE') zoneIssues.push({ level: 'info', msg: 'This zone uses $GENERATE. The generated records are not listed in the table, the directive itself is kept exactly as it is.' });
	}

	let errors = 0, warns = 0;
	for (const id in perRecord) for (const i of perRecord[id]) { if (i.level === 'error') errors++; else if (i.level === 'warn') warns++; }
	for (const i of zoneIssues) { if (i.level === 'error') errors++; else if (i.level === 'warn') warns++; }

	return { perRecord: perRecord, zone: zoneIssues, errors: errors, warnings: warns };
}

/* ##################################################################
	Ready made record sets
	------------------------------------------------------------------
	The handful of records that every domain ends up needing and that
	are easy to get subtly wrong. They are inserted as normal records
	and can be edited afterwards like any other.
################################################################## */
const TEMPLATES = {
	spf: {
		label: 'SPF - only the listed mail servers may send',
		build: () => [{ name: '@', type: 'TXT', rdata: quoteString('v=spf1 mx -all'), comment: 'SPF: this domain sends mail only through its MX hosts' }]
	},
	dmarc: {
		label: 'DMARC - report only, nothing is rejected yet',
		build: (o) => [{ name: '_dmarc', type: 'TXT', rdata: quoteString('v=DMARC1; p=none; rua=mailto:postmaster@' + bare(o)), comment: 'DMARC: start with p=none and read the reports before tightening it' }]
	},
	dkim: {
		label: 'DKIM - empty selector to paste a key into',
		build: () => [{ name: 'selector1._domainkey', type: 'TXT', rdata: quoteString('v=DKIM1; k=rsa; p='), comment: 'DKIM: paste the public key of your mail server after p=' }]
	},
	caa: {
		label: "CAA - only Let's Encrypt may issue certificates",
		build: (o) => [
			{ name: '@', type: 'CAA', rdata: '0 issue "letsencrypt.org"' },
			{ name: '@', type: 'CAA', rdata: '0 issuewild ";"', comment: 'no wildcard certificates for this domain' },
			{ name: '@', type: 'CAA', rdata: '0 iodef "mailto:postmaster@' + bare(o) + '"' }
		]
	},
	nomail: {
		label: 'No mail - this domain never sends or receives mail (RFC 7505)',
		build: () => [
			{ name: '@', type: 'MX', rdata: '0 .', comment: 'null MX: this domain accepts no mail' },
			{ name: '@', type: 'TXT', rdata: quoteString('v=spf1 -all') },
			{ name: '_dmarc', type: 'TXT', rdata: quoteString('v=DMARC1; p=reject;') }
		]
	},
	tlsrpt: {
		label: 'MTA-STS and TLS reporting for mail',
		build: (o) => [
			{ name: '_mta-sts', type: 'TXT', rdata: quoteString('v=STSv1; id=' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '01'), comment: 'raise the id after every change to the policy file' },
			{ name: '_smtp._tls', type: 'TXT', rdata: quoteString('v=TLSRPTv1; rua=mailto:postmaster@' + bare(o)) }
		]
	}
};

function bare(origin) { return String(origin || '').replace(/\.$/, ''); }

/* ##################################################################
	Editor state
################################################################## */
let cfg = {};
let zone = null;
let analysis = { perRecord: {}, zone: [], errors: 0, warnings: 0 };
let container = null;
let initialText = '';
let saving = false;
let history = [];
let future = [];
let refreshTimer = null;

const ui = {
	tab: 'records',
	search: '',
	types: new Set(),
	issuesOnly: false,
	showMeta: false,
	sort: { key: null, dir: 1 },
	expanded: new Set(),
	selected: new Set(),
	addType: 'A',
	rawTouched: false
};

function stateText() { return JSON.stringify({ origin: zone.origin, ttl: zone.ttl, ttlText: zone.ttlText, entries: zone.entries }); }

function pushHistory(text) {
	history.push(text);
	if (history.length > 60) history.shift();
	future.length = 0;
	pendingSnapshot = null;
}

function snapshot() { pushHistory(stateText()); }

/* Typing in a field changes the record on every keystroke so the checks stay
   live, which means the state to go back to has to be kept the moment the field
   is entered - by the time the change event arrives the old value is gone. */
let pendingSnapshot = null;

function beginEdit() { pendingSnapshot = stateText(); }

function commitEdit() {
	if (pendingSnapshot === null) { snapshot(); return; }
	const text = pendingSnapshot;
	pendingSnapshot = null;
	pushHistory(text);
}

function restore(text) {
	const data = JSON.parse(text);
	zone.origin = data.origin;
	zone.ttl = data.ttl;
	zone.ttlText = data.ttlText;
	zone.entries = data.entries;
}

function undo() {
	if (!history.length) { toast('Nothing to undo.'); return; }
	future.push(JSON.stringify({ origin: zone.origin, ttl: zone.ttl, ttlText: zone.ttlText, entries: zone.entries }));
	restore(history.pop());
	renderShell();
	toast('Undone.');
}

function redo() {
	if (!future.length) { toast('Nothing to redo.'); return; }
	history.push(JSON.stringify({ origin: zone.origin, ttl: zone.ttl, ttlText: zone.ttlText, entries: zone.entries }));
	restore(future.pop());
	renderShell();
	toast('Redone.');
}

function currentText() { return serializeZone(zone); }
function hasChanges() { return currentText() !== initialText; }

function recordById(id) { return zone.entries.find(e => e.id === id); }

/* ##################################################################
	Styles
################################################################## */
function injectStyles() {
	if (document.getElementById('dze-style')) return;
	const s = document.createElement('style');
	s.id = 'dze-style';
	s.textContent = `
	.dze { font-family: ui-sans-serif, system-ui, "Source Sans 3", sans-serif; font-size: 14px; color: #111; color-scheme: light; }
	.dze *, .dze *::before, .dze *::after { box-sizing: border-box; }
	.dze-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
	.dze-header { display: flex; align-items: flex-start; gap: 16px; padding: 12px 16px; border-bottom: 1px solid #f0f0ed; flex-wrap: wrap; }
	.dze-head-left { display: flex; align-items: center; gap: 10px; flex: 1 1 260px; min-width: 0; }
	.dze-dot { width: 9px; height: 9px; border-radius: 50%; background: #10b981; flex-shrink: 0; }
	.dze-dot.warn { background: #f59e0b; } .dze-dot.err { background: #dc2626; }
	.dze-zone-name { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; font-size: 15px; word-break: break-all; }
	.dze-sub { font-size: 12px; color: #6b7280; margin-top: 2px; }
	.dze-idn { font-size: 12px; color: #7c3aed; font-family: ui-monospace, monospace; }
	.dze-head-right { display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end; }
	.dze-field { display: flex; flex-direction: column; gap: 2px; }
	.dze-label { font-size: 10.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #9ca3af; }
	.dze-head-inp { font-family: ui-monospace, monospace; font-size: 13px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 4px 8px; background: #fff; color: #111; outline: none; }
	.dze-head-inp:focus { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.12); }
	#dze-origin { width: 230px; } #dze-default-ttl { width: 90px; }
	.dze-tabs { display: flex; align-items: center; gap: 2px; padding: 0 10px; background: #fafafa; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; }
	.dze-tab { border: none; background: transparent; padding: 9px 14px; font-size: 13px; font-weight: 600; color: #6b7280; cursor: pointer; border-bottom: 2px solid transparent; }
	.dze-tab:hover { color: #111; }
	.dze-tab.active { color: #2563eb; border-bottom-color: #2563eb; }
	.dze-tab-actions { margin-left: auto; display: flex; gap: 6px; padding: 5px 0; flex-wrap: wrap; }
	.dze-btn { display: inline-flex; align-items: center; gap: 5px; padding: 5px 10px; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid transparent; background: #f3f4f6; color: #374151; line-height: 1.4; }
	.dze-btn:hover { background: #e5e7eb; }
	.dze-btn[disabled] { opacity: .45; cursor: not-allowed; }
	.dze-btn-pri { background: #2563eb; color: #fff; } .dze-btn-pri:hover { background: #1d4ed8; }
	.dze-btn-ghost { background: transparent; color: #6b7280; padding: 4px 6px; }
	.dze-btn-ghost:hover { background: #f3f4f6; color: #111; }
	.dze-btn-danger:hover { background: #fef2f2; color: #dc2626; }
	.dze-toolbar { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-bottom: 1px solid #f0f0ed; flex-wrap: wrap; }
	.dze-search { flex: 1 1 200px; min-width: 160px; font-size: 13px; border: 1px solid #e5e7eb; border-radius: 7px; padding: 6px 10px; background: #fff; color: #111; outline: none; }
	.dze-search:focus { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.12); }
	.dze-chips { display: flex; gap: 5px; flex-wrap: wrap; align-items: center; }
	.dze-chip { display: inline-flex; align-items: center; gap: 5px; border: 1px solid #e5e7eb; background: #fff; border-radius: 99px; padding: 3px 9px; font-size: 11.5px; font-weight: 700; font-family: ui-monospace, monospace; cursor: pointer; color: #374151; }
	.dze-chip:hover { border-color: #9ca3af; }
	.dze-chip.on { background: #111; border-color: #111; color: #fff; }
	.dze-chip .dze-n { font-weight: 600; opacity: .65; }
	.dze-chip.issues { color: #b45309; border-color: #fcd34d; background: #fffbeb; }
	.dze-chip.issues.on { background: #b45309; border-color: #b45309; color: #fff; }
	.dze-toggle { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; color: #6b7280; cursor: pointer; user-select: none; }
	.dze-table-wrap { overflow-x: auto; max-height: 46vh; overflow-y: auto; }
	table.dze-table { width: 100%; border-collapse: collapse; font-size: 13px; }
	.dze-table th { position: sticky; top: 0; z-index: 2; text-align: left; padding: 7px 8px; font-size: 10.5px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #9ca3af; background: #fafafa; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
	.dze-table th.sortable { cursor: pointer; } .dze-table th.sortable:hover { color: #111; }
	.dze-table td { padding: 3px 6px; border-bottom: 1px solid #f7f7f5; vertical-align: middle; }
	.dze-table tr.dze-row:hover td { background: #f9fafb; }
	.dze-table tr.dze-row.sel td { background: #eff6ff; }
	.dze-table tr.dze-row.flash td { animation: dze-flash 1.4s ease-out; }
	@keyframes dze-flash { from { background: #fef08a; } to { background: transparent; } }
	.dze-inp { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12.5px; border: 1px solid transparent; border-radius: 5px; padding: 4px 6px; width: 100%; background: transparent; color: #111; outline: none; }
	.dze-inp:hover { border-color: #e5e7eb; background: #fff; }
	.dze-inp:focus { border-color: #2563eb; background: #fff; box-shadow: 0 0 0 2px rgba(37,99,235,.1); }
	.dze-inp.bad { border-color: #fca5a5; background: #fef2f2; }
	.dze-c-name { min-width: 130px; } .dze-c-rdata { min-width: 240px; width: 55%; }
	.dze-c-ttl input { width: 66px; } .dze-c-type input { width: 96px; font-weight: 700; }
	.dze-c-sel, .dze-c-flag { width: 28px; text-align: center; }
	.dze-c-act { white-space: nowrap; width: 96px; text-align: right; }
	.dze-flag { font-size: 13px; line-height: 1; cursor: help; }
	.dze-flag.err { color: #dc2626; } .dze-flag.warn { color: #d97706; } .dze-flag.info { color: #9ca3af; }
	.dze-row-meta td { background: #fcfcfb; color: #9ca3af; font-family: ui-monospace, monospace; font-size: 12px; }
	.dze-row-meta .dze-inp { color: #6b7280; }
	.dze-badge { display: inline-block; padding: 1px 7px; border-radius: 99px; font-size: 10px; font-weight: 700; letter-spacing: .04em; font-family: ui-monospace, monospace; background: #f3f4f6; color: #6b7280; }
	.dze-detail td { background: #fbfcfe; border-bottom: 1px solid #e5e7eb; padding: 12px 14px; }
	.dze-detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 10px; }
	.dze-detail-grid.narrow { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); }
	.dze-dfield { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
	.dze-dfield.wide { grid-column: 1 / -1; }
	.dze-dinp, .dze-dsel { font-family: ui-monospace, monospace; font-size: 12.5px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 5px 8px; background: #fff; color: #111; outline: none; width: 100%; }
	.dze-dinp:focus, .dze-dsel:focus { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.1); }
	textarea.dze-dinp { min-height: 62px; resize: vertical; }
	.dze-note { font-size: 11.5px; color: #6b7280; }
	.dze-typedesc { font-size: 12.5px; color: #374151; margin-bottom: 10px; }
	.dze-typedesc b { font-family: ui-monospace, monospace; }
	.dze-issues { margin-top: 10px; display: flex; flex-direction: column; gap: 4px; }
	.dze-issue { font-size: 12px; display: flex; gap: 7px; align-items: flex-start; line-height: 1.45; }
	.dze-issue .ic { flex-shrink: 0; font-weight: 700; }
	.dze-issue.error .ic { color: #dc2626; } .dze-issue.warn .ic { color: #d97706; } .dze-issue.info .ic { color: #6b7280; }
	.dze-issue.error { color: #991b1b; } .dze-issue.warn { color: #92400e; } .dze-issue.info { color: #4b5563; }
	.dze-add { border-top: 1px solid #e5e7eb; background: #f9fafb; padding: 10px 12px; }
	.dze-add-head { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
	.dze-add-grid { display: grid; grid-template-columns: minmax(120px,1fr) 78px 150px minmax(200px,2fr) auto; gap: 6px; align-items: start; }
	.dze-add-inp, .dze-add-sel { font-family: ui-monospace, monospace; font-size: 12.5px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 6px 8px; width: 100%; background: #fff; color: #111; outline: none; }
	.dze-add-inp:focus, .dze-add-sel:focus { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.1); }
	.dze-add-fields { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 6px; }
	.dze-empty { text-align: center; padding: 26px; color: #9ca3af; font-style: italic; }
	.dze-raw { width: 100%; min-height: 44vh; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12.5px; line-height: 1.5; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; background: #fcfcfc; color: #111; outline: none; white-space: pre; overflow-wrap: normal; overflow-x: auto; tab-size: 8; }
	.dze-raw:focus { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.1); }
	.dze-pane { padding: 0; }
	.dze-pane-pad { padding: 12px; }
	.dze-status { display: flex; align-items: center; gap: 12px; padding: 8px 14px; border-top: 1px solid #e5e7eb; background: #fafafa; font-size: 12px; color: #6b7280; flex-wrap: wrap; }
	.dze-status b { color: #111; }
	.dze-status .err { color: #dc2626; } .dze-status .warn { color: #b45309; } .dze-status .ok { color: #059669; }
	.dze-check-group { margin-bottom: 14px; }
	.dze-check-group h4 { font-size: 12.5px; margin: 0 0 6px 0; font-family: ui-monospace, monospace; color: #111; }
	.dze-jump { background: none; border: none; color: #2563eb; cursor: pointer; font-size: 12px; padding: 0; text-decoration: underline; }
	.dze-toast { position: fixed; bottom: 22px; right: 22px; background: #111; color: #fff; padding: 10px 16px; border-radius: 8px; font-size: 13px; opacity: 0; transform: translateY(6px); transition: all .18s; pointer-events: none; z-index: 20000; max-width: 60vw; }
	.dze-toast.show { opacity: 1; transform: translateY(0); }
	.dze-ro { padding: 10px 14px; background: #fffbeb; border-bottom: 1px solid #fde68a; color: #92400e; font-size: 12.5px; }
	@media (max-width: 820px) {
		.dze-add-grid { grid-template-columns: 1fr 1fr; }
		.dze-header { flex-direction: column; align-items: stretch; }
		.dze-table-wrap { max-height: none; }
	}
	`;
	document.head.appendChild(s);
}

/* ##################################################################
	Toast
################################################################## */
let toastTimer = null;
function toast(msg) {
	let el = document.getElementById('dze-toast');
	if (!el) { el = document.createElement('div'); el.id = 'dze-toast'; el.className = 'dze-toast'; el.setAttribute('role', 'status'); document.body.appendChild(el); }
	el.textContent = msg;
	el.classList.add('show');
	clearTimeout(toastTimer);
	toastTimer = setTimeout(() => el.classList.remove('show'), 2600);
}

/* ##################################################################
	Rendering
################################################################## */
function typeCounts() {
	const counts = new Map();
	for (const e of zone.entries) {
		if (e.kind !== 'record') continue;
		counts.set(e.type, (counts.get(e.type) || 0) + 1);
	}
	return counts;
}

function entryIssues(e) { return analysis.perRecord[e.id] || []; }

function worstLevel(list) {
	if (list.some(i => i.level === 'error')) return 'error';
	if (list.some(i => i.level === 'warn')) return 'warn';
	if (list.length) return 'info';
	return null;
}

function entrySearchText(e) {
	if (e.kind === 'record') return [e.name, e.type, e.rdata, e.comment || '', nameToUnicode(e.name) || ''].join(' ');
	if (e.kind === 'directive') return e.directive + ' ' + e.value;
	return e.text || '';
}

function visibleEntries() {
	const q = ui.search.trim().toLowerCase();
	let list = zone.entries.filter(function (e) {
		if (e.kind === 'blank') return false;
		if (e.kind === 'comment') return ui.showMeta;
		return true;
	});
	if (ui.types.size) list = list.filter(e => e.kind === 'record' && ui.types.has(e.type));
	if (ui.issuesOnly) list = list.filter(e => entryIssues(e).some(i => i.level === 'error' || i.level === 'warn'));
	if (q) list = list.filter(e => entrySearchText(e).toLowerCase().indexOf(q) > -1);
	if (ui.sort.key) {
		const k = ui.sort.key, dir = ui.sort.dir;
		list = list.slice().sort(function (a, b) {
			if (a.kind !== 'record' || b.kind !== 'record') return 0;
			let va, vb;
			if (k === 'ttl') { va = a.ttl === null ? zone.ttl : a.ttl; vb = b.ttl === null ? zone.ttl : b.ttl; return (va - vb) * dir; }
			if (k === 'name') { va = absName(a.name, zone.origin).toLowerCase().split('.').reverse().join('.'); vb = absName(b.name, zone.origin).toLowerCase().split('.').reverse().join('.'); }
			else { va = String(a[k] || '').toLowerCase(); vb = String(b[k] || '').toLowerCase(); }
			return va < vb ? -dir : (va > vb ? dir : 0);
		});
	}
	return list;
}

function typeDatalist() {
	const seen = {};
	const all = Object.keys(TYPES).filter(t => !TYPES[t].meta);
	for (const t of all) seen[t] = 1;
	for (const e of zone.entries) if (e.kind === 'record') seen[e.type] = 1;
	return '<datalist id="dze-typelist">' + Object.keys(seen).sort().map(t => '<option value="' + esc(t) + '"></option>').join('') + '</datalist>';
}

function typeSelectHtml(id, selected, cls) {
	const groups = {};
	for (const t of Object.keys(TYPES)) {
		const info = TYPES[t];
		if (info.meta) continue;
		if (TYPE_ORDER.indexOf(t) > -1) continue;
		(groups[info.cat] = groups[info.cat] || []).push(t);
	}
	let html = '<select id="' + id + '" class="' + (cls || 'dze-add-sel') + '" aria-label="Record type">';
	html += '<optgroup label="Most used">';
	for (const t of TYPE_ORDER) html += '<option value="' + t + '"' + (t === selected ? ' selected' : '') + '>' + t + ' - ' + esc(shortDesc(t)) + '</option>';
	html += '</optgroup>';
	for (const cat of ['service', 'dnssec', 'crypto', 'infra', 'legacy']) {
		if (!groups[cat]) continue;
		html += '<optgroup label="' + esc(CAT[cat].label) + '">';
		for (const t of groups[cat].sort()) html += '<option value="' + esc(t) + '"' + (t === selected ? ' selected' : '') + '>' + esc(t) + ' - ' + esc(shortDesc(t)) + '</option>';
		html += '</optgroup>';
	}
	html += '<optgroup label="Anything else"><option value="__generic"' + (selected === '__generic' ? ' selected' : '') + '>TYPE&lt;number&gt; - a type this editor does not know (RFC 3597)</option></optgroup>';
	html += '</select>';
	return html;
}

function shortDesc(t) {
	const info = TYPES[t];
	if (!info) return '';
	const d = info.desc.split('.')[0];
	return d.length > 62 ? d.slice(0, 60) + '...' : d;
}

function renderShell() {
	if (!container) return;
	analysis = analyzeZone(zone);
	const unicodeOrigin = nameToUnicode(zone.origin);
	const dot = analysis.errors ? 'err' : (analysis.warnings ? 'warn' : '');

	container.innerHTML = '<div class="dze">' + typeDatalist() +
		'<div class="dze-card">' +
			'<div class="dze-header">' +
				'<div class="dze-head-left">' +
					'<span class="dze-dot ' + dot + '" aria-hidden="true"></span>' +
					'<div style="min-width:0;">' +
						'<div class="dze-zone-name">' + esc(zone.origin) + (unicodeOrigin ? ' <span class="dze-idn">(' + esc(unicodeOrigin) + ')</span>' : '') + '</div>' +
						'<div class="dze-sub" id="dze-headsub"></div>' +
					'</div>' +
				'</div>' +
				'<div class="dze-head-right">' +
					'<div class="dze-field"><label class="dze-label" for="dze-origin">Zone origin</label>' +
						'<input class="dze-head-inp" id="dze-origin" value="' + esc(zone.origin) + '" spellcheck="false"></div>' +
					'<div class="dze-field"><label class="dze-label" for="dze-default-ttl">Default TTL</label>' +
						'<input class="dze-head-inp" id="dze-default-ttl" value="' + esc(zone.ttlText || formatTTL(zone.ttl)) + '" spellcheck="false"></div>' +
				'</div>' +
			'</div>' +
			(cfg.readonly ? '<div class="dze-ro"><b>Read only.</b> ' + esc(cfg.readonlyText) + '</div>' : '') +
			'<div class="dze-tabs" role="tablist">' +
				'<button class="dze-tab" data-tab="records" role="tab">Records</button>' +
				'<button class="dze-tab" data-tab="raw" role="tab">Raw zone file</button>' +
				'<button class="dze-tab" data-tab="check" role="tab">Check <span id="dze-tab-check-badge"></span></button>' +
				'<div class="dze-tab-actions">' +
					'<button class="dze-btn" data-action="undo" title="Undo (Ctrl+Z)"><i class="bi bi-arrow-counterclockwise"></i> Undo</button>' +
					'<button class="dze-btn" data-action="redo" title="Redo (Ctrl+Y)"><i class="bi bi-arrow-clockwise"></i> Redo</button>' +
					'<button class="dze-btn" data-action="format" title="Rewrite every record with aligned columns">Format</button>' +
					'<button class="dze-btn" data-action="copy" title="Copy the whole zone file"><i class="bi bi-copy"></i> Copy</button>' +
					'<button class="dze-btn" data-action="download" title="Download the zone file"><i class="bi bi-download"></i> Download</button>' +
				'</div>' +
			'</div>' +
			'<div class="dze-pane" id="dze-pane-records">' +
				'<div class="dze-toolbar">' +
					'<input class="dze-search" id="dze-search" type="search" placeholder="Search name, data or comment..." value="' + esc(ui.search) + '" aria-label="Search records">' +
					'<div class="dze-chips" id="dze-chips"></div>' +
					'<label class="dze-toggle"><input type="checkbox" id="dze-show-meta"' + (ui.showMeta ? ' checked' : '') + '> Comments</label>' +
				'</div>' +
				'<div class="dze-table-wrap">' +
					'<table class="dze-table" aria-label="DNS records">' +
						'<thead><tr>' +
							'<th class="dze-c-sel"><input type="checkbox" id="dze-sel-all" aria-label="Select all shown records"></th>' +
							'<th class="sortable" data-sort="name">Name</th>' +
							'<th class="sortable" data-sort="ttl">TTL</th>' +
							'<th class="sortable" data-sort="type">Type</th>' +
							'<th>Record data</th>' +
							'<th class="dze-c-flag" title="Findings"><i class="bi bi-exclamation-triangle"></i></th>' +
							'<th class="dze-c-act"></th>' +
						'</tr></thead>' +
						'<tbody id="dze-tbody"></tbody>' +
					'</table>' +
				'</div>' +
				'<div class="dze-add">' +
					'<div class="dze-add-head">' +
						'<b style="font-size:12.5px;">Add a record</b>' +
						'<select class="dze-add-sel" id="dze-template" style="width:auto;max-width:100%;" aria-label="Insert a ready made record set">' +
							'<option value="">Insert a ready made set...</option>' +
							Object.keys(TEMPLATES).map(k => '<option value="' + k + '">' + esc(TEMPLATES[k].label) + '</option>').join('') +
						'</select>' +
						'<span id="dze-bulk" style="margin-left:auto;"></span>' +
					'</div>' +
					'<div class="dze-add-grid">' +
						'<input class="dze-add-inp" id="dze-new-name" placeholder="Name, @ for the domain itself" spellcheck="false" aria-label="Name">' +
						'<input class="dze-add-inp" id="dze-new-ttl" placeholder="' + esc(formatTTL(zone.ttl)) + '" spellcheck="false" aria-label="TTL">' +
						typeSelectHtml('dze-new-type', ui.addType) +
						'<div id="dze-new-fields"></div>' +
						'<button class="dze-btn dze-btn-pri" data-action="add-record" style="height:33px;"><i class="bi bi-plus-lg"></i> Add</button>' +
					'</div>' +
				'</div>' +
			'</div>' +
			'<div class="dze-pane dze-pane-pad" id="dze-pane-raw" hidden>' +
				'<div class="dze-note" style="margin-bottom:8px;">The zone file as it is stored. Edit it here and press <b>Apply</b> to load it back into the record table - everything you do not touch keeps its exact text. The <code>$ORIGIN</code> line is removed again when the zone is saved, bind takes the origin from its own configuration.</div>' +
				'<textarea class="dze-raw" id="dze-raw" spellcheck="false" wrap="off" aria-label="Raw zone file"></textarea>' +
				'<div style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;">' +
					'<button class="dze-btn dze-btn-pri" data-action="raw-apply"><i class="bi bi-check-lg"></i> Apply</button>' +
					'<button class="dze-btn" data-action="raw-reset">Discard changes in this view</button>' +
					'<span class="dze-note" id="dze-raw-note" style="align-self:center;"></span>' +
				'</div>' +
			'</div>' +
			'<div class="dze-pane dze-pane-pad" id="dze-pane-check" hidden></div>' +
			'<div class="dze-status" id="dze-status"></div>' +
		'</div></div>';

	renderAddFields();
	refresh();
	setTab(ui.tab, true);
	applyReadonly();
	bindEvents();
}

function levelIcon(level) { return level === 'error' ? '!' : (level === 'warn' ? '!' : 'i'); }

function issueHtml(list) {
	return list.map(i => '<div class="dze-issue ' + (i.level === 'warn' ? 'warn' : i.level) + '"><span class="ic">' + levelIcon(i.level) + '</span><span>' + esc(i.msg) + '</span></div>').join('');
}

function rowHtml(e) {
	if (e.kind === 'comment') {
		return '<tr class="dze-row dze-row-meta" data-id="' + e.id + '" data-kind="comment">' +
			'<td class="dze-c-sel"></td>' +
			'<td colspan="4"><input class="dze-inp" data-field="text" value="' + esc(e.text) + '" spellcheck="false" aria-label="Comment line"></td>' +
			'<td class="dze-c-flag"><span class="dze-badge">note</span></td>' +
			'<td class="dze-c-act"><button class="dze-btn dze-btn-ghost dze-btn-danger" data-action="delete" data-id="' + e.id + '" title="Delete this comment"><i class="bi bi-trash"></i></button></td></tr>';
	}
	if (e.kind === 'directive' || e.kind === 'raw') {
		const text = e.kind === 'directive' ? (e.directive + ' ' + e.value) : e.text;
		const badge = e.kind === 'directive' ? esc(e.directive) : 'unparsed';
		return '<tr class="dze-row dze-row-meta" data-id="' + e.id + '" data-kind="' + e.kind + '">' +
			'<td class="dze-c-sel"></td>' +
			'<td colspan="4"><input class="dze-inp" data-field="text" value="' + esc(text) + '" spellcheck="false" aria-label="Directive"></td>' +
			'<td class="dze-c-flag"><span class="dze-badge">' + badge + '</span></td>' +
			'<td class="dze-c-act"><button class="dze-btn dze-btn-ghost dze-btn-danger" data-action="delete" data-id="' + e.id + '" title="Delete this line"><i class="bi bi-trash"></i></button></td></tr>';
	}

	const issues = entryIssues(e);
	const level = worstLevel(issues);
	const col = typeColor(e.type);
	const idn = nameToUnicode(e.name);
	const flag = level
		? '<span class="dze-flag ' + (level === 'warn' ? 'warn' : level) + '" title="' + esc(issues.map(i => i.msg).join('\n')) + '">' + (level === 'info' ? 'i' : '!') + '</span>'
		: '';
	const ttlVal = (e.ttlText === null || e.ttlText === undefined) ? '' : formatTTL(parseTTL(e.ttlText) === null ? e.ttlText : parseTTL(e.ttlText));
	const expanded = ui.expanded.has(e.id);

	return '<tr class="dze-row' + (ui.selected.has(e.id) ? ' sel' : '') + '" data-id="' + e.id + '" data-kind="record">' +
		'<td class="dze-c-sel"><input type="checkbox" class="dze-rowsel"' + (ui.selected.has(e.id) ? ' checked' : '') + ' aria-label="Select record"></td>' +
		'<td class="dze-c-name"><input class="dze-inp" data-field="name" value="' + esc(e.name) + '" placeholder="@" spellcheck="false" aria-label="Name"' + (idn ? ' title="' + esc(idn) + '"' : '') + '></td>' +
		'<td class="dze-c-ttl"><input class="dze-inp" data-field="ttl" value="' + esc(ttlVal) + '" placeholder="' + esc(formatTTL(zone.ttl)) + '" spellcheck="false" aria-label="TTL" title="' + esc(humanTTL(e.ttl === null ? zone.ttl : e.ttl)) + (e.ttl === null ? ' (inherited from the zone default)' : '') + '"></td>' +
		'<td class="dze-c-type"><input class="dze-inp" data-field="type" list="dze-typelist" value="' + esc(e.type) + '" style="color:' + col + '" spellcheck="false" aria-label="Record type"></td>' +
		'<td class="dze-c-rdata"><input class="dze-inp" data-field="rdata" value="' + esc(e.rdata) + '" placeholder="' + esc(typeHint(e.type)) + '" spellcheck="false" aria-label="Record data"></td>' +
		'<td class="dze-c-flag">' + flag + '</td>' +
		'<td class="dze-c-act">' +
			'<button class="dze-btn dze-btn-ghost" data-action="toggle-detail" data-id="' + e.id + '" title="Show the individual values and the checks" aria-expanded="' + (expanded ? 'true' : 'false') + '"><i class="bi bi-' + (expanded ? 'chevron-up' : 'chevron-down') + '"></i></button>' +
			'<button class="dze-btn dze-btn-ghost" data-action="duplicate" data-id="' + e.id + '" title="Duplicate"><i class="bi bi-files"></i></button>' +
			'<button class="dze-btn dze-btn-ghost dze-btn-danger" data-action="delete" data-id="' + e.id + '" title="Delete"><i class="bi bi-trash"></i></button>' +
		'</td></tr>' +
		(expanded ? detailHtml(e) : '');
}

function fieldInputHtml(fld, value, idPrefix) {
	const id = idPrefix + '-' + fld.key;
	const display = fieldToDisplay(fld, value);
	let input;
	if (fld.kind === 'enum' && fld.values && !fld.free) {
		const keys = fld.numeric ? Object.keys(fld.values) : fld.values;
		let opts = '';
		let known = false;
		for (const k of keys) {
			const val = fld.numeric ? k : k;
			const text = fld.numeric ? (k + ' - ' + fld.values[k]) : k;
			const sel = String(display).toLowerCase() === String(val).toLowerCase();
			if (sel) known = true;
			opts += '<option value="' + esc(val) + '"' + (sel ? ' selected' : '') + '>' + esc(text) + '</option>';
		}
		if (!known && display !== '') opts = '<option value="' + esc(display) + '" selected>' + esc(display) + ' - not assigned</option>' + opts;
		input = '<select class="dze-dsel" id="' + id + '" data-fkey="' + fld.key + '">' + opts + '</select>';
	} else if (fld.kind === 'enum' && fld.free) {
		input = '<input class="dze-dinp" id="' + id + '" data-fkey="' + fld.key + '" list="' + id + '-list" value="' + esc(display) + '" spellcheck="false">' +
			'<datalist id="' + id + '-list">' + fld.values.map(v => '<option value="' + esc(v) + '"></option>').join('') + '</datalist>';
	} else if (fld.kind === 'text' || fld.kind === 'base64' || fld.kind === 'hex') {
		input = '<textarea class="dze-dinp" id="' + id + '" data-fkey="' + fld.key + '" spellcheck="false" placeholder="' + esc(fld.hint || '') + '">' + esc(display) + '</textarea>';
	} else {
		input = '<input class="dze-dinp" id="' + id + '" data-fkey="' + fld.key + '" value="' + esc(display) + '" spellcheck="false" placeholder="' + esc(fld.hint || '') + '">';
	}
	const wide = (fld.kind === 'text' || fld.kind === 'base64' || fld.kind === 'hex' || fld.rest) ? ' wide' : '';
	return '<div class="dze-dfield' + wide + '"><label class="dze-label" for="' + id + '">' + esc(fld.label) + '</label>' + input +
		(fld.note ? '<span class="dze-note">' + esc(fld.note) + '</span>' : '') + '</div>';
}

function detailHtml(e) {
	const info = typeInfo(e.type);
	const issues = entryIssues(e);
	let body = '';

	if (info) {
		body += '<div class="dze-typedesc"><b>' + esc(e.type) + '</b> &ndash; ' + esc(info.desc) + ' <span class="dze-note">(' + esc(info.rfc || '') + ')</span></div>';
	} else {
		body += '<div class="dze-typedesc"><b>' + esc(e.type) + '</b> &ndash; unknown to this editor, the data is kept unchanged.</div>';
	}

	if (info && info.fields) {
		const vals = rdataToFields(e.type, e.rdata);
		if (vals) {
			body += '<div class="dze-detail-grid' + (info.fields.length > 4 ? ' narrow' : '') + '" data-detail-for="' + e.id + '">' +
				info.fields.map(fld => fieldInputHtml(fld, vals[fld.key], 'f-' + e.id)).join('') + '</div>';
		} else {
			body += '<div class="dze-note" style="margin-bottom:8px;">The data of this record does not follow the usual layout of a ' + esc(e.type) + ' record (' + info.fields.map(x => esc(x.label)).join(', ') + '), so it is edited as one piece in the table above. Nothing is rewritten.</div>';
		}
	} else {
		body += '<div class="dze-note" style="margin-bottom:8px;">Edit the record data directly in the table above.</div>';
	}

	body += '<div class="dze-detail-grid" style="margin-top:10px;">' +
		'<div class="dze-dfield wide"><label class="dze-label" for="c-' + e.id + '">Comment on this record</label>' +
		'<input class="dze-dinp" id="c-' + e.id + '" data-comment-for="' + e.id + '" value="' + esc(e.comment || '') + '" spellcheck="false" placeholder="kept in the zone file after a semicolon"></div></div>';

	if (issues.length) body += '<div class="dze-issues">' + issueHtml(issues) + '</div>';
	else body += '<div class="dze-issues"><div class="dze-issue info"><span class="ic">&#10003;</span><span>No findings for this record.</span></div></div>';

	return '<tr class="dze-detail" data-detail-row="' + e.id + '"><td colspan="7">' + body + '</td></tr>';
}

/* Zones with thousands of records exist (reverse zones above all). Everything is
   kept in memory and written back, only the table stops after this many rows -
   a browser does not stay usable with ten thousand input fields on screen. */
const ROW_LIMIT = 400;

function renderRows() {
	const tbody = document.getElementById('dze-tbody');
	if (!tbody) return;
	const list = visibleEntries();
	const total = zone.entries.filter(e => e.kind === 'record').length;
	if (!list.length) {
		tbody.innerHTML = '<tr><td colspan="7" class="dze-empty">' +
			(total === 0
				? (cfg.readonly ? 'This zone holds no records.' : 'This zone has no records yet. Add the first one below.')
				: 'No record matches the current filter.') + '</td></tr>';
	} else {
		const shownList = list.slice(0, ROW_LIMIT);
		tbody.innerHTML = shownList.map(rowHtml).join('') +
			(list.length > ROW_LIMIT
				? '<tr><td colspan="7" class="dze-empty">' + (list.length - ROW_LIMIT) + ' more entries are not drawn. Narrow the list down with the search box or a type filter - they are all still part of the zone and are saved with it.</td></tr>'
				: '');
	}
	const shown = list.filter(e => e.kind === 'record').length;
	const info = document.getElementById('dze-shown');
	if (info) {
		if (list.length > ROW_LIMIT) info.textContent = 'drawing the first ' + ROW_LIMIT + ' of ' + shown;
		else info.textContent = shown === total ? '' : ('showing ' + shown + ' of ' + total);
	}
	applyReadonly();
}

/* A replicated domain is shown, not edited - its zone data belongs to the master
   server that owns it. The table stays fully usable for reading and filtering. */
function applyReadonly() {
	if (!cfg.readonly || !container) return;
	const keep = { 'dze-search': 1, 'dze-show-meta': 1 };
	for (const el of container.querySelectorAll('input, textarea, select')) {
		if (keep[el.id]) continue;
		if (el.tagName === 'SELECT' || el.type === 'checkbox') el.disabled = true;
		else el.readOnly = true;
	}
	for (const el of container.querySelectorAll('[data-action]')) {
		const a = el.dataset.action;
		if (a === 'copy' || a === 'download' || a === 'toggle-detail') continue;
		el.style.display = 'none';
	}
	const add = container.querySelector('.dze-add');
	if (add) add.style.display = 'none';
	const selAll = container.querySelector('#dze-sel-all');
	if (selAll) selAll.style.visibility = 'hidden';
}

function renderChips() {
	const el = document.getElementById('dze-chips');
	if (!el) return;
	const counts = typeCounts();
	const total = zone.entries.filter(e => e.kind === 'record').length;
	const issueCount = zone.entries.filter(e => e.kind === 'record' && entryIssues(e).some(i => i.level === 'error' || i.level === 'warn')).length;
	const types = Array.from(counts.keys()).sort(function (a, b) {
		const d = counts.get(b) - counts.get(a);
		return d !== 0 ? d : (a < b ? -1 : 1);
	});
	let html = '<button class="dze-chip' + (ui.types.size === 0 ? ' on' : '') + '" data-type="*" title="Show every record">ALL <span class="dze-n">' + total + '</span></button>';
	for (const t of types) {
		html += '<button class="dze-chip' + (ui.types.has(t) ? ' on' : '') + '" data-type="' + esc(t) + '" style="' + (ui.types.has(t) ? '' : 'color:' + typeColor(t) + ';') + '" title="' + esc((typeInfo(t) ? typeInfo(t).desc : 'Unknown record type')) + '">' + esc(t) + ' <span class="dze-n">' + counts.get(t) + '</span></button>';
	}
	if (issueCount) html += '<button class="dze-chip issues' + (ui.issuesOnly ? ' on' : '') + '" data-type="!" title="Only records with a finding">FINDINGS <span class="dze-n">' + issueCount + '</span></button>';
	html += '<span class="dze-note" id="dze-shown" style="margin-left:4px;"></span>';
	el.innerHTML = html;
}

function renderStatus() {
	const el = document.getElementById('dze-status');
	if (!el) return;
	const total = zone.entries.filter(e => e.kind === 'record').length;
	const changed = hasChanges();
	el.innerHTML =
		'<span><b>' + total + '</b> record' + (total !== 1 ? 's' : '') + '</span>' +
		'<span class="' + (analysis.errors ? 'err' : 'ok') + '"><b>' + analysis.errors + '</b> error' + (analysis.errors !== 1 ? 's' : '') + '</span>' +
		'<span class="' + (analysis.warnings ? 'warn' : 'ok') + '"><b>' + analysis.warnings + '</b> warning' + (analysis.warnings !== 1 ? 's' : '') + '</span>' +
		'<span style="margin-left:auto;">' + (changed ? '<b>Unsaved changes</b> - press Save to write them to the zone.' : 'No changes yet.') + '</span>';

	const sub = document.getElementById('dze-headsub');
	/* Written as an escape on purpose: the file has to survive being served with
	   any charset the web server happens to announce. */
	if (sub) sub.textContent = total + ' record' + (total !== 1 ? 's' : '') + ' \u00b7 default TTL ' + formatTTL(zone.ttl) + ' (' + humanTTL(zone.ttl) + ')';

	const badge = document.getElementById('dze-tab-check-badge');
	if (badge) badge.innerHTML = analysis.errors ? '<span class="dze-badge" style="background:#fee2e2;color:#b91c1c;">' + analysis.errors + '</span>'
		: (analysis.warnings ? '<span class="dze-badge" style="background:#fef3c7;color:#92400e;">' + analysis.warnings + '</span>' : '');

	const dot = container.querySelector('.dze-dot');
	if (dot) dot.className = 'dze-dot ' + (analysis.errors ? 'err' : (analysis.warnings ? 'warn' : ''));

	const bulk = document.getElementById('dze-bulk');
	if (bulk) {
		bulk.innerHTML = ui.selected.size
			? '<span class="dze-note" style="margin-right:6px;">' + ui.selected.size + ' selected</span>' +
			  '<button class="dze-btn" data-action="bulk-ttl"><i class="bi bi-clock"></i> Set TTL</button> ' +
			  '<button class="dze-btn dze-btn-danger" data-action="bulk-delete"><i class="bi bi-trash"></i> Delete</button>'
			: '';
	}
}

function renderCheck() {
	const el = document.getElementById('dze-pane-check');
	if (!el) return;
	let html = '';
	if (analysis.zone.length) {
		html += '<div class="dze-check-group"><h4>The zone as a whole</h4><div class="dze-issues">' + issueHtml(analysis.zone) + '</div></div>';
	}
	let any = false;
	for (const e of zone.entries) {
		if (e.kind !== 'record') continue;
		const issues = entryIssues(e);
		if (!issues.length) continue;
		any = true;
		html += '<div class="dze-check-group"><h4>' + esc(e.name) + ' ' + esc(e.type) + ' ' + esc(e.rdata.length > 60 ? e.rdata.slice(0, 58) + '...' : e.rdata) +
			' <button class="dze-jump" data-jump="' + e.id + '">show</button></h4><div class="dze-issues">' + issueHtml(issues) + '</div></div>';
	}
	if (!analysis.zone.length && !any) {
		html = '<div class="dze-issue info" style="font-size:13px;"><span class="ic">&#10003;</span><span>Nothing to report. Keep in mind that the final word belongs to bind: the background worker runs named-checkzone over this zone and only activates it when that passes.</span></div>';
	} else {
		html += '<div class="dze-note" style="margin-top:14px;">These checks are advice, not a verdict. The zone is written out and handed to named-checkzone by the background worker - if that refuses it, the previous working zone keeps serving the domain.</div>';
	}
	el.innerHTML = html;
}

/* Re-run the analysis and repaint everything that depends on it. */
function refresh() {
	analysis = analyzeZone(zone);
	renderChips();
	renderRows();
	renderStatus();
	renderCheck();
}

function refreshSoon() {
	clearTimeout(refreshTimer);
	refreshTimer = setTimeout(function () {
		analysis = analyzeZone(zone);
		renderChips();
		renderStatus();
		renderCheck();
		updateRowFlags();
	}, 220);
}

/* Update the finding markers without touching the inputs the user is typing in. */
function updateRowFlags() {
	if (!container) return;
	const rows = container.querySelectorAll('tr.dze-row[data-kind="record"]');
	for (const tr of rows) {
		const e = recordById(tr.dataset.id);
		if (!e) continue;
		const issues = entryIssues(e);
		const level = worstLevel(issues);
		const cell = tr.querySelector('.dze-c-flag');
		if (!cell) continue;
		cell.innerHTML = level ? '<span class="dze-flag ' + (level === 'warn' ? 'warn' : level) + '" title="' + esc(issues.map(i => i.msg).join('\n')) + '">' + (level === 'info' ? 'i' : '!') + '</span>' : '';
		const rd = tr.querySelector('[data-field="rdata"]');
		if (rd) rd.classList.toggle('bad', issues.some(i => i.level === 'error'));
	}
}

function setTab(tab, silent) {
	/* Leaving the raw view applies what is written there, otherwise the edit
	   would silently disappear when the user switches back. */
	if (ui.tab === 'raw' && tab !== 'raw' && !silent) applyRaw(true);
	ui.tab = tab;
	for (const pane of ['records', 'raw', 'check']) {
		const el = document.getElementById('dze-pane-' + pane);
		if (el) el.hidden = (pane !== tab);
	}
	for (const btn of container.querySelectorAll('.dze-tab')) btn.classList.toggle('active', btn.dataset.tab === tab);
	if (tab === 'raw') {
		const ta = document.getElementById('dze-raw');
		if (ta) { ta.value = currentText(); ui.rawTouched = false; setRawNote(''); }
	}
	if (tab === 'check') renderCheck();
}

function setRawNote(text) {
	const el = document.getElementById('dze-raw-note');
	if (el) el.textContent = text;
}

function applyRaw(quiet) {
	const ta = document.getElementById('dze-raw');
	if (!ta) return;
	if (ta.value === currentText()) { if (!quiet) toast('The raw view already matches the records.'); return; }
	snapshot();
	const parsed = parseZone(ta.value, { origin: cfg.origin, ttl: zone.ttl });
	zone = parsed;
	ui.expanded.clear();
	ui.selected.clear();
	ui.rawTouched = false;
	refresh();
	toast('Zone loaded from the raw view.');
}

/* ##################################################################
	Mutations
################################################################## */
function newRecord(rec) {
	return {
		id: uid(), kind: 'record', raw: null, dirty: true,
		name: rec.name || '@',
		ttlText: rec.ttlText === undefined ? null : rec.ttlText,
		ttl: rec.ttlText === undefined || rec.ttlText === null ? null : parseTTL(rec.ttlText),
		cls: rec.cls || 'IN',
		type: (rec.type || 'A').toUpperCase(),
		rdata: rec.rdata || '',
		comment: rec.comment || null
	};
}

function addRecords(list, where) {
	snapshot();
	const made = list.map(newRecord);
	if (where === 'after' && list.afterId) {
		const idx = zone.entries.findIndex(e => e.id === list.afterId);
		zone.entries.splice(idx + 1, 0, ...made);
	} else {
		zone.entries.push(...made);
	}
	refresh();
	return made;
}

function markDirty(e) { e.dirty = true; e.raw = null; }

function renderAddFields() {
	const host = document.getElementById('dze-new-fields');
	if (!host) return;
	const type = ui.addType;
	if (type === '__generic') {
		host.innerHTML = '<div class="dze-add-fields">' +
			'<input class="dze-add-inp" id="dze-new-generic-type" placeholder="type number, e.g. 65534" spellcheck="false" aria-label="Record type number">' +
			'<input class="dze-add-inp" id="dze-new-rdata" placeholder="\\# 5 0102030405" spellcheck="false" aria-label="Record data">' +
			'</div>';
		return;
	}
	const info = typeInfo(type);
	if (info && info.fields) {
		host.innerHTML = '<div class="dze-add-fields">' + info.fields.map(function (fld) {
			if (fld.kind === 'enum' && fld.values && !fld.free) {
				const keys = fld.numeric ? Object.keys(fld.values) : fld.values;
				return '<select class="dze-add-sel" data-nfkey="' + fld.key + '" aria-label="' + esc(fld.label) + '" title="' + esc(fld.label) + '">' +
					keys.map(k => '<option value="' + esc(k) + '">' + esc(fld.numeric ? (k + ' - ' + fld.values[k]) : k) + '</option>').join('') + '</select>';
			}
			return '<input class="dze-add-inp" data-nfkey="' + fld.key + '" placeholder="' + esc(fld.label + (fld.hint ? ': ' + fld.hint : '')) + '" spellcheck="false" aria-label="' + esc(fld.label) + '">';
		}).join('') + '</div>';
	} else {
		host.innerHTML = '<input class="dze-add-inp" id="dze-new-rdata" placeholder="' + esc(typeHint(type) || 'record data') + '" spellcheck="false" aria-label="Record data">';
	}
}

function collectNewRecord() {
	const nameInp = document.getElementById('dze-new-name');
	const ttlInp = document.getElementById('dze-new-ttl');
	let name = nameInp.value.trim();
	if (name === '') name = '@';
	if (hasUnicode(name)) name = nameToASCII(name);
	const ttlRaw = ttlInp.value.trim();
	if (ttlRaw !== '' && parseTTL(ttlRaw) === null) { toast('The TTL has to be a number of seconds or a time like 1h, 2d, 1w.'); return null; }

	let type = ui.addType;
	let rdata = '';
	if (type === '__generic') {
		const num = (document.getElementById('dze-new-generic-type') || {}).value;
		if (!/^\d+$/.test(String(num).trim())) { toast('Enter the numeric type of the record, for example 65534.'); return null; }
		type = 'TYPE' + String(num).trim();
		rdata = (document.getElementById('dze-new-rdata') || {}).value.trim();
	} else {
		const info = typeInfo(type);
		if (info && info.fields) {
			const vals = {};
			for (const el of container.querySelectorAll('#dze-new-fields [data-nfkey]')) {
				const fld = info.fields.find(x => x.key === el.dataset.nfkey);
				vals[el.dataset.nfkey] = displayToField(fld, el.value);
			}
			rdata = fieldsToRdata(type, vals);
		} else {
			rdata = ((document.getElementById('dze-new-rdata') || {}).value || '').trim();
		}
	}
	if (rdata === '') { toast('The record needs data.'); return null; }
	return { name: name, ttlText: ttlRaw === '' ? null : ttlRaw, type: type, rdata: rdata };
}

/* ##################################################################
	Events
################################################################## */
let eventsBound = false;

function bindEvents() {
	/* Everything is delegated to the container and bound exactly once. The card
	   itself is rebuilt on undo and on setZoneData, so listeners attached to the
	   elements inside it would pile up and run the same action several times. */
	if (eventsBound) return;
	eventsBound = true;

	/* Remember the state before a field is edited, see commitEdit(). */
	container.addEventListener('focusin', function (ev) {
		const t = ev.target;
		if (t && t.matches && t.matches('[data-field], [data-fkey], [data-comment-for], #dze-origin, #dze-default-ttl')) beginEdit();
	});

	/* Tabs and every button in the editor. */
	container.addEventListener('click', function (ev) {
		const tab = ev.target.closest('.dze-tab');
		if (tab) { setTab(tab.dataset.tab); return; }

		const chip = ev.target.closest('.dze-chip');
		if (chip) {
			const t = chip.dataset.type;
			if (t === '*') { ui.types.clear(); ui.issuesOnly = false; }
			else if (t === '!') { ui.issuesOnly = !ui.issuesOnly; }
			else if (ui.types.has(t)) ui.types.delete(t);
			else ui.types.add(t);
			renderChips();
			renderRows();
			return;
		}

		const sortTh = ev.target.closest('th.sortable');
		if (sortTh) {
			const key = sortTh.dataset.sort;
			if (ui.sort.key === key) { if (ui.sort.dir === 1) ui.sort.dir = -1; else ui.sort = { key: null, dir: 1 }; }
			else ui.sort = { key: key, dir: 1 };
			renderRows();
			return;
		}

		const jump = ev.target.closest('[data-jump]');
		if (jump) { jumpTo(jump.dataset.jump); return; }

		const btn = ev.target.closest('[data-action]');
		if (!btn) return;
		const action = btn.dataset.action;
		const id = btn.dataset.id;

		if (action === 'undo') { undo(); return; }
		if (action === 'redo') { redo(); return; }
		if (action === 'copy') { copyText(currentText(), 'Zone file copied to the clipboard.'); return; }
		if (action === 'download') { downloadZone(); return; }
		if (action === 'format') { formatAll(); return; }
		if (action === 'raw-apply') { applyRaw(); setTab('records'); return; }
		if (action === 'raw-reset') { const ta = document.getElementById('dze-raw'); if (ta) { ta.value = currentText(); ui.rawTouched = false; setRawNote(''); toast('Raw view reset.'); } return; }

		if (action === 'toggle-detail') {
			if (ui.expanded.has(id)) ui.expanded.delete(id); else ui.expanded.add(id);
			renderRows();
			return;
		}
		if (action === 'delete') {
			const idx = zone.entries.findIndex(e => e.id === id);
			if (idx < 0) return;
			snapshot();
			zone.entries.splice(idx, 1);
			ui.expanded.delete(id);
			ui.selected.delete(id);
			refresh();
			toast('Record deleted. Undo brings it back.');
			return;
		}
		if (action === 'duplicate') {
			const idx = zone.entries.findIndex(e => e.id === id);
			if (idx < 0) return;
			const src = zone.entries[idx];
			snapshot();
			const copy = newRecord({ name: src.name, ttlText: src.ttlText, cls: src.cls, type: src.type, rdata: src.rdata, comment: src.comment });
			zone.entries.splice(idx + 1, 0, copy);
			refresh();
			return;
		}
		if (action === 'add-record') {
			const rec = collectNewRecord();
			if (!rec) return;
			addRecords([rec]);
			document.getElementById('dze-new-name').value = '';
			document.getElementById('dze-new-ttl').value = '';
			renderAddFields();
			const nameInp = document.getElementById('dze-new-name');
			if (nameInp) nameInp.focus();
			toast('Record added.');
			return;
		}
		if (action === 'bulk-delete') {
			if (!ui.selected.size) return;
			snapshot();
			zone.entries = zone.entries.filter(e => !ui.selected.has(e.id));
			const n = ui.selected.size;
			ui.selected.clear();
			refresh();
			toast(n + ' records deleted. Undo brings them back.');
			return;
		}
		if (action === 'bulk-ttl') {
			const answer = window.prompt('New TTL for the ' + ui.selected.size + ' selected records.\nA number of seconds or a time like 1h, 2d, 1w. Leave empty to fall back to the zone default (' + formatTTL(zone.ttl) + ').', formatTTL(zone.ttl));
			if (answer === null) return;
			const trimmed = answer.trim();
			if (trimmed !== '' && parseTTL(trimmed) === null) { toast('That is not a time value.'); return; }
			snapshot();
			for (const e of zone.entries) {
				if (!ui.selected.has(e.id) || e.kind !== 'record') continue;
				e.ttlText = trimmed === '' ? null : trimmed;
				e.ttl = trimmed === '' ? null : parseTTL(trimmed);
				markDirty(e);
			}
			refresh();
			return;
		}
	});

	/* Select all / row selection / the controls of the header and the add row. */
	container.addEventListener('change', function (ev) {
		if (ev.target.id === 'dze-origin') {
			let v = ev.target.value.trim();
			if (hasUnicode(v)) { v = nameToASCII(v); toast('Converted to punycode: ' + v); }
			if (v !== '' && !isAbsolute(v)) v += '.';
			if (v === zone.origin) { ev.target.value = v; return; }
			commitEdit();
			zone.origin = v;
			ev.target.value = v;
			refresh();
			return;
		}
		if (ev.target.id === 'dze-default-ttl') {
			const v = parseTTL(ev.target.value);
			if (v === null || v < 0) { ev.target.value = zone.ttlText || formatTTL(zone.ttl); toast('The TTL has to be a number of seconds or a time like 1h, 2d, 1w.'); return; }
			if (v === zone.ttl) { ev.target.value = formatTTL(v); return; }
			commitEdit();
			zone.ttl = v;
			zone.ttlText = formatTTL(v);
			ev.target.value = zone.ttlText;
			refresh();
			return;
		}
		if (ev.target.id === 'dze-show-meta') { ui.showMeta = ev.target.checked; renderRows(); return; }
		if (ev.target.id === 'dze-new-type') { ui.addType = ev.target.value; renderAddFields(); return; }
		if (ev.target.id === 'dze-template') {
			const key = ev.target.value;
			ev.target.value = '';
			if (!key || !TEMPLATES[key]) return;
			const made = addRecords(TEMPLATES[key].build(zone.origin));
			toast(made.length + ' record' + (made.length !== 1 ? 's' : '') + ' inserted - check the values before saving.');
			return;
		}
		if (ev.target.id === 'dze-sel-all') {
			const list = visibleEntries().filter(e => e.kind === 'record');
			if (ev.target.checked) for (const e of list) ui.selected.add(e.id);
			else for (const e of list) ui.selected.delete(e.id);
			renderRows();
			renderStatus();
			return;
		}
		if (ev.target.classList.contains('dze-rowsel')) {
			const tr = ev.target.closest('tr[data-id]');
			if (!tr) return;
			if (ev.target.checked) ui.selected.add(tr.dataset.id); else ui.selected.delete(tr.dataset.id);
			tr.classList.toggle('sel', ev.target.checked);
			renderStatus();
			return;
		}
	});

	/* Live edits in the table, the search box and the raw view. */
	container.addEventListener('input', function (ev) {
		if (ev.target.id === 'dze-search') { ui.search = ev.target.value; renderRows(); return; }
		if (ev.target.id === 'dze-raw') {
			ui.rawTouched = true;
			setRawNote(ev.target.value.split('\n').length + ' lines - not applied yet');
			return;
		}
		const field = ev.target.dataset ? ev.target.dataset.field : null;
		if (!field) return;
		const tr = ev.target.closest('tr[data-id]');
		if (!tr) return;
		const e = recordById(tr.dataset.id);
		if (!e) return;
		if (e.kind !== 'record') { e.text = ev.target.value; markDirty(e); refreshSoon(); return; }
		if (field === 'rdata') { e.rdata = ev.target.value; markDirty(e); refreshSoon(); }
		else if (field === 'name') { e.name = ev.target.value; markDirty(e); refreshSoon(); }
	});

	container.addEventListener('change', function (ev) {
		const t = ev.target;

		/* Directive / comment lines. */
		const trMeta = t.closest('tr[data-kind="comment"], tr[data-kind="directive"], tr[data-kind="raw"]');
		if (trMeta && t.dataset.field === 'text') {
			const e = recordById(trMeta.dataset.id);
			if (!e) return;
			commitEdit();
			if (e.kind === 'directive') {
				const parts = t.value.trim().split(/\s+/);
				e.directive = parts[0].toUpperCase();
				e.value = parts.slice(1).join(' ');
			} else {
				e.text = t.value;
			}
			markDirty(e);
			refresh();
			return;
		}

		/* Structured fields in the detail row. */
		if (t.dataset.fkey) {
			const grid = t.closest('[data-detail-for]');
			if (!grid) return;
			const e = recordById(grid.dataset.detailFor);
			if (!e) return;
			const info = typeInfo(e.type);
			if (!info || !info.fields) return;
			const vals = {};
			for (const el of grid.querySelectorAll('[data-fkey]')) {
				const fld = info.fields.find(x => x.key === el.dataset.fkey);
				if (!fld) continue;
				let value = displayToField(fld, el.value);
				if (fld.kind === 'name' && hasUnicode(el.value)) { toast('Converted to punycode: ' + value); el.value = value; }
				vals[fld.key] = value;
			}
			commitEdit();
			e.rdata = fieldsToRdata(e.type, vals);
			markDirty(e);
			const rowInp = container.querySelector('tr[data-id="' + e.id + '"] [data-field="rdata"]');
			if (rowInp) rowInp.value = e.rdata;
			refresh();
			return;
		}

		if (t.dataset.commentFor) {
			const e = recordById(t.dataset.commentFor);
			if (!e) return;
			commitEdit();
			e.comment = t.value.trim() === '' ? null : t.value.trim();
			markDirty(e);
			refresh();
			return;
		}

		/* Table fields. */
		const tr = t.closest('tr[data-id][data-kind="record"]');
		if (!tr || !t.dataset.field) return;
		const e = recordById(tr.dataset.id);
		if (!e) return;
		const field = t.dataset.field;
		commitEdit();

		if (field === 'ttl') {
			const v = t.value.trim();
			if (v === '') { e.ttlText = null; e.ttl = null; }
			else {
				const parsed = parseTTL(v);
				if (parsed === null) { toast('"' + v + '" is not a time value - use seconds or 1h, 2d, 1w.'); t.value = e.ttlText === null ? '' : e.ttlText; history.pop(); return; }
				e.ttlText = formatTTL(parsed);
				e.ttl = parsed;
				t.value = e.ttlText;
			}
		} else if (field === 'type') {
			const v = t.value.trim().toUpperCase();
			if (!/^[A-Z][A-Z0-9-]*$/.test(v)) { toast('"' + t.value + '" is not a record type. Unknown types are written as TYPE<number> (RFC 3597).'); t.value = e.type; history.pop(); return; }
			e.type = v;
			t.style.color = typeColor(v);
		} else if (field === 'name') {
			let v = t.value.trim();
			if (hasUnicode(v)) { v = nameToASCII(v); toast('Converted to punycode: ' + v); }
			e.name = v === '' ? '@' : v;
			t.value = e.name;
		} else if (field === 'rdata') {
			e.rdata = t.value;
		}
		markDirty(e);
		refresh();
	});

	/* Enter adds the record that is being typed in the add row. */
	container.addEventListener('keydown', function (ev) {
		/* A tab inside the raw zone view is a separator, not a way out of the field. */
		if (ev.key === 'Tab' && ev.target.id === 'dze-raw' && !ev.ctrlKey && !ev.altKey) {
			ev.preventDefault();
			const ta = ev.target;
			const a = ta.selectionStart, b = ta.selectionEnd;
			ta.value = ta.value.slice(0, a) + '\t' + ta.value.slice(b);
			ta.selectionStart = ta.selectionEnd = a + 1;
			ui.rawTouched = true;
			return;
		}
		if (ev.key === 'Enter' && ev.target.closest('.dze-add-grid')) {
			ev.preventDefault();
			const rec = collectNewRecord();
			if (!rec) return;
			addRecords([rec]);
			document.getElementById('dze-new-name').value = '';
			document.getElementById('dze-new-ttl').value = '';
			renderAddFields();
			return;
		}
		if (ev.key === 'Escape') {
			const tr = ev.target.closest('tr[data-id]');
			if (tr && ui.expanded.has(tr.dataset.id)) { ui.expanded.delete(tr.dataset.id); renderRows(); }
		}
	});
}

function jumpTo(id) {
	ui.types.clear();
	ui.issuesOnly = false;
	ui.search = '';
	const s = document.getElementById('dze-search');
	if (s) s.value = '';
	ui.expanded.add(id);
	setTab('records');
	renderChips();
	renderRows();
	const tr = container.querySelector('tr[data-id="' + id + '"]');
	if (tr) {
		tr.scrollIntoView({ block: 'center', behavior: 'smooth' });
		tr.classList.add('flash');
		setTimeout(() => tr.classList.remove('flash'), 1500);
	}
}

/* Rewrite every record in the editor's own layout. Explicitly asked for -
   it touches lines the user never edited, which is otherwise never done. */
function formatAll() {
	if (!window.confirm('Rewrite every record with aligned columns?\n\nThe records themselves stay exactly as they are, only their spacing changes. Comments and directives keep their place.')) return;
	snapshot();
	for (const e of zone.entries) { if (e.kind === 'record') markDirty(e); }
	refresh();
	toast('Zone formatted.');
}

function copyText(text, msg) {
	if (navigator.clipboard && window.isSecureContext) {
		navigator.clipboard.writeText(text).then(() => toast(msg), () => fallbackCopy(text, msg));
	} else fallbackCopy(text, msg);
}

function fallbackCopy(text, msg) {
	const ta = document.createElement('textarea');
	ta.value = text;
	ta.style.position = 'fixed';
	ta.style.opacity = '0';
	document.body.appendChild(ta);
	ta.select();
	try { document.execCommand('copy'); toast(msg); } catch (e) { toast('Copying failed - select the text in the raw view instead.'); }
	document.body.removeChild(ta);
}

function downloadZone() {
	const name = bare(zone.origin) || 'zone';
	const blob = new Blob([currentText()], { type: 'text/plain;charset=utf-8' });
	const a = document.createElement('a');
	a.href = URL.createObjectURL(blob);
	a.download = 'db.' + name;
	document.body.appendChild(a);
	a.click();
	setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
	toast('Zone file downloaded.');
}

/* ##################################################################
	Init and public interface
################################################################## */
function readConfig(el) {
	const domain = (el.dataset.domain || '').trim();
	let origin = (el.dataset.origin || '').trim();
	if (!origin && domain) origin = domain + '.';
	if (!origin) origin = '.';
	if (origin !== '.' && !isAbsolute(origin)) origin += '.';
	const ttl = parseTTL(el.dataset.defaultTtl || '');
	return {
		origin: origin,
		ttl: ttl === null ? 3600 : ttl,
		readonly: el.dataset.readonly === '1',
		readonlyText: el.dataset.readonlyText || 'This zone is shown as it is stored. Nothing changed here can be saved.'
	};
}

function init() {
	const el = document.getElementById('dns-zone-editor');
	if (!el) return;
	injectStyles();
	container = el;
	cfg = readConfig(el);

	const rawZone = (el.dataset.zone !== undefined && el.dataset.zone !== null) ? el.dataset.zone : (window.dnsZoneData || '');
	zone = parseZone(rawZone, { origin: cfg.origin, ttl: cfg.ttl });
	initialText = serializeZone(zone);

	renderShell();

	/* Ctrl+Z / Ctrl+Y while the editor is on screen. */
	document.addEventListener('keydown', function (ev) {
		if (!container || !document.body.contains(container)) return;
		if (!(ev.ctrlKey || ev.metaKey)) return;
		const k = ev.key.toLowerCase();
		if (k === 'z' && !ev.shiftKey) {
			if (ev.target.closest('.dze-raw')) return;      /* let the textarea do its own undo */
			ev.preventDefault(); undo();
		} else if (k === 'y' || (k === 'z' && ev.shiftKey)) {
			if (ev.target.closest('.dze-raw')) return;
			ev.preventDefault(); redo();
		}
	});

	/* Closing the page with unsaved records is almost always a mistake. */
	window.addEventListener('beforeunload', function (ev) {
		if (saving || cfg.readonly || !hasChanges()) return;
		ev.preventDefault();
		ev.returnValue = '';
		return '';
	});

	window.DNSZoneEditor = {
		/* Used by the save button of the page. */
		getZoneData: function () {
			if (ui.tab === 'raw') {
				const ta = document.getElementById('dze-raw');
				if (ta && ta.value !== currentText()) applyRaw(true);
			}
			return currentText();
		},
		getRecords: function () { return zone.entries.filter(e => e.kind === 'record').map(r => Object.assign({}, r)); },
		setZoneData: function (text) { snapshot(); zone = parseZone(text, { origin: cfg.origin, ttl: cfg.ttl }); renderShell(); },
		addRecord: function (rec) { return addRecords([rec])[0]; },
		hasChanges: hasChanges,
		issueCounts: function () { return { errors: analysis.errors, warnings: analysis.warnings }; },
		markSaving: function () { saving = true; },
		/* Called right before the page submits the form. Gives the user one last
		   look at what is wrong, but never refuses to save - bind decides. */
		confirmSave: function () {
			if (ui.tab === 'raw') {
				const ta = document.getElementById('dze-raw');
				if (ta && ta.value !== currentText()) applyRaw(true);
			}
			analysis = analyzeZone(zone);
			if (cfg.readonly) {
				window.alert('This is a replicated domain. Its zone data is maintained on the master server that owns it and cannot be saved here.');
				return false;
			}
			if (analysis.errors > 0) {
				const lines = [];
				for (const i of analysis.zone) if (i.level === 'error') lines.push('- ' + i.msg);
				for (const e of zone.entries) {
					if (e.kind !== 'record') continue;
					for (const i of entryIssues(e)) if (i.level === 'error') lines.push('- ' + e.name + ' ' + e.type + ': ' + i.msg);
				}
				const shown = lines.slice(0, 8).join('\n') + (lines.length > 8 ? '\n- ... and ' + (lines.length - 8) + ' more' : '');
				if (!window.confirm(analysis.errors + ' problem' + (analysis.errors !== 1 ? 's were' : ' was') + ' found in this zone:\n\n' + shown +
					'\n\nbind will most likely refuse it, in which case the domain keeps answering with the zone that is currently active. Save anyway?')) return false;
			}
			saving = true;
			return true;
		}
	};
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();

/* #DZE_UI_END# */

})();
