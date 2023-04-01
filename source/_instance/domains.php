<?php
	if($permsobj->hasPerm($user->user_id, "perm_domains_api") OR $user->user_rank == 0) {
?>	

<div class="content_box" >
	Here you can find Replicated Domains from External Master Servers!<br />[This Servers Slave Domains]<br />
	<a href="./?site=apidomains" style="padding: none;margin: none;">View Replicated Domains</a>
</div>
<?php
	} if($permsobj->hasPerm($user->user_id, "perm_domains_bind") OR $user->user_rank == 0) {
?>	
<div class="content_box" >
	Here you can view Domains from Local Bind File which is configured in the settigns.php File! <br />[This Servers Master Domains]<br />
	<a href="./?site=binddomains" style="padding: none;margin: none;">View Local Domains</a>
</div>
<?php
	} if($permsobj->hasPerm($user->user_id, "perm_userdomains") OR $user->user_rank == 0) {
?>	
<!-- <div class="content_box" >
	View your own registered Master Domains!<br />[Your Account Domains]<br />
	<a href="./?site=userdomains" style="padding: none;margin: none;">View My Domains</a>
</div> -->
<?php
	}
	if($permsobj->hasPerm($user->user_id, "perm_alldomains") OR $user->user_rank == 0) {
?>	
<!-- <div class="content_box" >
	View all user registered master domains on this server!<br />[All Account Domains]<br />
	<a href="./?site=alldomains" style="padding: none;margin: none;">View All User Domains</a>
</div> -->
<?php
	}
?>	