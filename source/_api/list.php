<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  API File to Fetch List of a Server Domains */
	require_once("../settings.php");
	if($ipbl->isblocked()) { echo "ip-blacklisted"; exit(); }
	if(is_numeric(dnshttp_api_token_relay($mysql, @$_POST["token"])) OR is_numeric(dnshttp_api_token_relay($mysql, @$_GET["token"]))) { 
		$domar	=	array();
		$ar = $mysql->select("SELECT domain FROM "._TABLE_DOMAIN_BIND_." WHERE set_no_replicate = 0", true);
		if(is_array($ar)) {
			foreach($ar AS $key => $value) { array_push($domar, trim($value["domain"]));}
			echo serialize($domar);
		} else { echo "error-domain-no-exist"; }
	}  else { echo "error-token"; $ipbl->raise(); }
	exit();
?>