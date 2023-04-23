<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  Domain Setup Handle File */
	function currentFormFunction($mysql, $id) { return dnshttp_bind_get($mysql, $id); }
	$currentFormtable = _TABLE_DOMAIN_BIND_;
	$currentFormperm = "domainmgr";
	$currentFormloc = "binddomains";
	require_once("./_instance/domains_int.php");
 ?>