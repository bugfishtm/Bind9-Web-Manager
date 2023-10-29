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
if(!$permsobj->hasPerm($user->user_id, "debug") AND $user->user_rank != 0) { echo "<div class='content_box'>You do not have Permission!</div>"; } else {	
	echo '<div class="content_box">';
	echo '<span style="font-size: 22px;"><b>MySQL Execution Errors [Limit 1000]</b></span><br />Here you may see SQL Errors, <b>This area is only for developers</b>!<br />Dont panic if you see a lot of errors here, its okay, the file you need to take an eye on are the replication logs/protocols in the "<a href="./?site=logs">Replication / Protocol</a>" section! Besides that you maybe find useful information at the "<a href="'._HELP_.'"  rel="noopener" target="_blank">Help</a>" section...<br /><div style="text-align:left;">';
	$res	=	$mysql->select("SELECT * FROM "._TABLE_LOG_MYSQL_."  ORDER BY id DESC LIMIT 1000", true); 	
		echo '<style>.hoverdiv345:hover div{background: #363636 !important;}</style>';
		echo '<style>.hoverdiv345:hover{background: #363636 !important;}</style>';
	if(is_array($res)) { 
		foreach ($res AS $key => $value) { 	
			echo "<hr>";
			echo '<div class="hoverdiv345">';
			echo "<font color='red'>Error at: ".$value["creation"]."</font><br />";
			echo "Initial Query<br />";
			echo "<textarea style='width: 100%;max-width:100%;background: rgba(0,0,0,0.9); color: white; width: 100%;max-width: 100%;min-width: 100%;min-height: 20px;' readonly>".$value["init"]." </textarea><br />";
			echo "Exception<br />";
			echo "<textarea style='width: 100%;max-width:100%;background: rgba(0,0,0,0.9); color: white; width: 100%;max-width: 100%;min-width: 100%;min-height: 20px;' readonly>".$value["exception"]."</textarea><br />";
			echo "SQL Error<br />";
			echo "<textarea style='width: 100%;max-width:100%;background: rgba(0,0,0,0.9); color: white; width: 100%;max-width: 100%;min-width: 100%;min-height: 20px;' readonly>".$value["sqlerror"]."</textarea><br />";
			echo '</div>';
			echo '<br clear="left"/>';
		}
	} else { echo "No Data Available..."; }
	echo '</div></div>';
 }
?>	