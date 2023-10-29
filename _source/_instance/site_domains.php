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
	if($permsobj->hasPerm($user->user_id, "domainmgr") OR $user->user_rank == 0) {
		
	$count = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." ", true);
	if(is_array($count)) { $count = count($count); } else { $count = 0; } 
?>	

<div class="content_box" >
	<b>Here you can find Replicated Domains from External Master Servers!</b><br />
	Here you can see all domains which have been replicated from other remote dns servers, which have been set up in the "Replication" section!. For more informations about this visit my "<a href="<?php echo _HELP_; ?>" rel="noopener" target="_blank">Help</a>" page!<br />
	<font color="lightgrey">Current Replicated Domains: <?php echo $count; ?></font><br />
	<a href="./?site=apidomains" class="sysbutton">View Slave Domains</a>
</div>
<?php
	
	$count = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_."", true);
	if(is_array($count)) { $count = count($count); } else { $count = 0; } 
	
?>	
<div class="content_box" >
	<b>Here you can view all Local Master Domains</b><br />
	Here are all domains which are master on this local system, you can not see replicated domains here from other servers! For more informations about this visit my "<a href="<?php echo _HELP_; ?>" rel="noopener" target="_blank">Help</a>" page!<br />
	<font color="lightgrey">Current Local Domains: <?php echo $count; ?></font><br />
	<a href="./?site=binddomains" class="sysbutton">View Master Domains</a>
</div>
<?php
	$count = $mysql->select("SELECT * FROM "._TABLE_CONFLICT_." WHERE solved = '0'", true);
	if(is_array($count)) { $count = count($count); } else { $count = 0; } 
	
	$count1 = $mysql->select("SELECT * FROM "._TABLE_CONFLICT_." WHERE solved <> '0'", true);
	if(is_array($count1)) { $count1 = count($count1); } else { $count1 = 0; } 
?>	
<div class="content_box" >
	<b>Here you can see solved and unsolved domain conflicts!</b><br /> A Conflict exists, for example if a domain is duplicated and no preferred domain has been selected! For more informations about this visit the "<a href="<?php echo _HELP_; ?>" rel="noopener" target="_blank">Help</a>" page!</b><br />
	<font color="lightgrey">Unsolved Conflicts: <b><?php echo $count; ?></b></font><br />
	<font color="lightgrey">Solved Conflicts: <b><?php echo $count1; ?></b></font><br />
	<a href="./?site=conflict" class="sysbutton">View Domain Conflicts</a>
</div> 
<?php
	}
?>