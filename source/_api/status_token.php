<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  Status API with Security Token */
	require_once("../settings.php");
	if($ipbl->isblocked()) { echo "ip-blacklisted"; exit(); }
	if(is_numeric(dnshttp_api_token_relay($mysql, @$_POST["token"]))) {
	echo "online"; } else { echo "token-error"; $ipbl->raise(); }
	exit();
?>