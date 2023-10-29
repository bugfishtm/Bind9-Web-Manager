<?php 
	/* 	
		@@@@@@@   @@@  @@@   @@@@@@@@  @@@@@@@@  @@@   @@@@@@   @@@  @@@  
		@@@@@@@@  @@@  @@@  @@@@@@@@@  @@@@@@@@  @@@  @@@@@@@   @@@  @@@  
		@@!  @@@  @@!  @@@  !@@        @@!       @@!  !@@       @@!  @@@  
		!@   @!@  !@!  @!@  !@!        !@!       !@!  !@!       !@!  @!@  
		@!@!@!@   @!@  !@!  !@! @!@!@  @!!!:!    !!@  !!@@!!    @!@!@!@!  
		!!!@!!!!  !@!  !!!  !!! !!@!!  !!!!!:    !!!   !!@!!!   !!!@!!!!  
		!!:  !!!  !!:  !!!  :!!   !!:  !!:       !!:       !:!  !!:  !!!  
		:!:  !:!  :!:  !:!  :!:   !::  :!:       :!:      !:!   :!:  !:!  
		 :: ::::  ::::: ::   ::: ::::   ::        ::  :::: ::   ::   :::  
		:: : ::    : :  :    :: :: :    :        :    :: : :     :   : :  
		   ____         _     __                      __  __         __           __  __
		  /  _/ _    __(_)__ / /    __ _____  __ __  / /_/ /  ___   / /  ___ ___ / /_/ /
		 _/ /  | |/|/ / (_-</ _ \  / // / _ \/ // / / __/ _ \/ -_) / _ \/ -_|_-</ __/_/ 
		/___/  |__,__/_/___/_//_/  \_, /\___/\_,_/  \__/_//_/\__/ /_.__/\__/___/\__(_)  
								  /___/                           
		Bugfish - DNSHTTP Software / MIT License
		// Autor: Jan-Maurice Dahlmanns (Bugfish)
		// Website: www.bugfish.eu 
	*/
	function int_prerp_serve($srv) {
		$string = "";
		if($newarray = unserialize($srv) ) {
			foreach($newarray as $key => $value) {
				if(isset($value["domain_type"])) {$asd = $value["domain_type"];} else { $asd = "Local"; }
				if(isset($value["fk_server"])) {$asasd = $value["fk_server"];} else { $asasd = "Local"; }
				$string .= "Type: ".@$asd." | ServerID: ".$asasd."<br />";
			}
			return $string;
		} else {
			return "Unknown";
		}
 	}
	
	
	if(!$permsobj->hasPerm($user->user_id, "domainmgr") AND $user->user_rank != 0) {  echo "<div class='content_box'>No Permission!</div>"; } else {
		
		$ar = $mysql->select("SELECT * FROM "._TABLE_LOG_." WHERE section = 'replication' ORDER BY id DESC LIMIT 1", false);
		
	echo '<div class="content_box">Here you can see and solve domain conflicts. Unsolved conflicts will be marked yellow! See more at the <a href="'._HELP_.'" target="_blank" rel="noopener">Documentation</a>. You can solve conflicts in the "<a href="./?site=domains">Domains</a>" Section at the Master or Slave Domain overview, you can choose a preferred domain there...<br /><b>Keep in mind, this site only gets updated, if the cronjob sync.php is executed! The current data you see has been last updated: <font color="lime">'.@$ar["creation"].'</font></b></div><div class="content_box"> ';

		
		$curissue	=	mysqli_query($mysql->mysqlcon, "SELECT * FROM "._TABLE_CONFLICT_." ORDER BY id DESC");
		$run = false;
		while ($curissuer	=	mysqli_fetch_array($curissue) ) { 
			$run = true;
			$string = "";
			
			if($curissuer["solved"] == 0 AND is_numeric($curissuer["solved"])) { 	echo '<fieldset style="background: yellow; color: black; margin-bottom: 5px; border: none;">';echo '<b>Conflicted Domain</b>: <b style="color: red;">'.@$curissuer["domain"].'</b><br /><b>Conflicted Servers</b>:<br /> '.@int_prerp_serve($curissuer["servers"]).' ';	}
			else { echo '<fieldset style="background: darkgreen; margin-bottom: 5px;border: none;">';echo "<div  class='sysbutton' style='background: green; color: black !important;margin: 5px; padding: 2px;' href='#'>Solved</div> "; echo '<b>Conflicted Domain</b>: <b style="color: lime;">'.@$curissuer["domain"].'</b><br /><b>Conflicted Servers</b>:<br /> '.@int_prerp_serve($curissuer["servers"]).'<b>Solved and Used Domain</b>:<br /> ';	echo $curissuer["solved"]; }
			echo '</fieldset>';	
		}
		if(!$run) {echo "There are no conflicts!<br />We are all god!";}
	echo '</div>';}
?>	