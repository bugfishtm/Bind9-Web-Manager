<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  API File to Fetch Content of a Domain */
	require_once("../settings.php");
	if($ipbl->isblocked()) { echo "ip-blacklisted"; exit(); }
	if(is_numeric(dnshttp_api_token_relay($mysql, @$_POST["token"]))) { 
		if($x = dnshttp_bind_domain_name_exists($mysql, @$_POST["domain"])) { 
			$domar	=	array();
			$ar = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE id = ".$x."", false);
			if(is_array($ar)) {
				if($ar["set_prefer_manual"] == 1) { echo $ar["content"]; }
				else {
echo '$TTL        3600
@       IN      SOA     '._HOSTNMAE_.'. '.$ar["dns_mail"].' (
                        '.$ar["dns_serial"].'       ; serial, todays date + todays serial #
                        '.$ar["dns_refresh"].'              ; refresh, seconds
                        '.$ar["dns_retry"].'              ; retry, seconds
                        '.$ar["dns_expire"].'              ; expire, seconds
                        '.$ar["dns_minimum"].' )            ; minimum, seconds
;					
';
					
			$ar2 = $mysql->select("SELECT * FROM "._TABLE_RECORD_." WHERE fk_domain = ".$ar["id"]."", false);
			if(is_array($ar2)) {
				foreach($ar2 as $key => $value) {
					echo ''.$ar["record_domain"].'   '.$ar["record_ttl"].'      '.$ar["record_type"].'      '.$ar["record_priority"].'      '.$ar["record_value"].'
					';
				}
					
			}
					
				}
			} else { echo "error-no-content"; }
		} else { echo "error-domain-no-exist"; }
	} else { echo "error-token"; $ipbl->raise(); }
	exit();
?>