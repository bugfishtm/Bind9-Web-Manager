<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  General Domain Handling and View File */
if(!$permsobj->hasPerm($user->user_id, "domainmgr") AND $user->user_rank != 0) { echo "<div class='content_box'>You do not have Permission!</div>"; } else { 

 /*if(@$_GET["addcontent"] == "true") { ?>	
		<div class="internal_popup">
			<div class="internal_popup_inner">
				<div class="internal_popup_title">Domain: <?php echo htmlspecialchars($x["domain"]); ?></div>
				<form method="post">
			<?php
				echo "<textarea placerholder='Domain Content' style='background: rgba(0,0,0,0.9); color: white; width: 100%;max-width: 100%;height: 120px;resize: none;' readonly>
\$TTL        3600
@       IN      SOA     "._HOSTNMAE_.". ".str_replace("@", ".", _USER_DOMAIN_MAIL_)." (
	".date("Ymd")." ; serial, generated
	"._USER_DOMAIN_REFRESH_." ; refresh, seconds
	"._USER_DOMAIN_RETRY_." ; retry, seconds
	"._USER_DOMAIN_EXPIRE_." ; expire, seconds
	"._USER_DOMAIN_MINIMUM_." ) ; minimum, seconds ;</textarea><br />";
	
				echo "<textarea placerholder='Domain Content' style='background: rgba(0,0,0,0.9); color: white; width: 100%;max-width: 100%;max-height: 40vh;height: 30vh;'>".$x["content"]."</textarea><br />";	
			?>
				<input type="submit"  class='internal_popup_submit' href='./?site=<?php echo $currentFormloc; ?>&id=<?php echo $_GET["dopref"]?>'>
				</form><div class="internal_popup_submit"><a href="./?site=<?php echo $currentFormloc; ?>" class="hoverblackfontas">Cancel</a></div>		
			</div>
		</div>
<?php }*/ echo '<style>.hoverdiv345:hover div{background: #363636 !important;}.hoverblackfontas:hover{color: black !important;}</style>';

 /*if($x = currentFormFunction($mysql, @$_GET["editcontent"])) { 	
	 if($x["domain_type"] == "user") { ?>	
		<div class="internal_popup">
			<div class="internal_popup_inner">
				<div class="internal_popup_title">Domain: <?php echo htmlspecialchars($x["domain"]); ?></div>
				
				<?php
					echo "<textarea placerholder='Domain Content' style='background: rgba(0,0,0,0.9); color: white; width: 100%;max-width: 100%;max-height: 40vh;height: 30vh;'>".$x["content"]."</textarea><br />";	
			?>
				
				<div class="internal_popup_submit"><a href="./?site=<?php echo $currentFormloc; ?>"  class="hoverblackfontas">Cancel</a></div>		
			</div>
		</div>
	<?php } ?>
<?php } */

 if($x = currentFormFunction($mysql, @$_GET["showcontent"])) { ?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Domain: <?php echo htmlspecialchars($x["domain"]); ?></div>
			
			<?php echo "<textarea style='background: rgba(0,0,0,0.9); color: white; width: 100%;max-width: 100%;max-height: 40vh;height: 30vh;' readonly>".$x["content"]."</textarea><br />";?>
			
			<div class="internal_popup_submit"><a href="./?site=<?php echo $currentFormloc; ?>"  class="hoverblackfontas">Cancel</a></div>		
		</div>
	</div>
<?php } ?>

<?php if($x = currentFormFunction($mysql, @$_GET["showdata"])) { ?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Domain: <?php echo htmlspecialchars($x["domain"]); ?></div>
			Here you can see some data about this domain which is not shown at the overview page...<br /><br />
			<b>Date Informations</b> <br />
				<b>Creation:</b> <?php echo $x["creation"];?><br />
				<b>Modification:</b> <?php echo $x["modification"];?><br />
				<b>Last Update:</b> <?php echo $x["last_update"];?><br /><br />
			<b>Source Informations</b> <br />
				<?php if(isset($x["domain_type"])) { ?><b>Domain Type:</b> <?php echo $x["domain_type"];?><br /><b>Zonefile:</b> <?php echo $x["zone_path"];?><br /><br /><?php } ?>
				<?php if($xy = dnshttp_server_get($mysql, @$x["fk_server"])) { ?><b>Server:</b> <?php echo $xy["api_path"];?><br /><br /><?php } ?>
				
			<b>Current Zonefile Check Output:</b> <br />	
				<textarea style="max-width: 100%;width: 100%;min-width: 100%;" readonly><?php echo $x["zonecheck_message"];?></textarea><br /><br />

			<b>Fail-Updated Zonefile Check Output:</b> <br />	
				<textarea style="max-width: 100%;width: 100%;min-width: 100%;" readonly><?php echo $x["zonecheck_failmessage"];?></textarea><br /><br />
			<div class="internal_popup_submit"><a href="./?site=<?php echo $currentFormloc; ?>"  class="hoverblackfontas">Cancel</a></div>		
		</div>
	</div>
<?php } ?>
<?php if($x = currentFormFunction($mysql, @$_GET["dodeldom"])) {  ?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Domain: <?php echo htmlspecialchars($x["domain"]); ?></div>
				Do you want to delete this domain? This makes mostly no sense, this domain will be fetched again! Only do this if this domain has errors which you can not solve. Sometimes it can than help to delete the domain from the local server and let it re-register the domain automatically via the cronjob. (Use only to fix Errors, the domain will most likely not stay deleted!<br />
			<div class="internal_popup_submit"><a style="background: #434343;margin-bottom: 5px;" class="hoverdiv345" href='./?site=<?php echo $currentFormloc; ?>&dodeldomd=<?php echo $_GET["dodeldom"]?>&csrf=<?php echo $csrf->get();?>'>Delete Domain</a><a href="./?site=<?php echo $currentFormloc; ?>"  class="hoverblackfontas">Cancel</a></div>		
		</div>
	</div>
<?php } ?>
<?php if($x = currentFormFunction($mysql, @$_GET["dopref"])) {  ?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Domain: <?php echo htmlspecialchars($x["domain"]); ?></div>
				Do you want to prefer this domain in conflitcts? <br />
			<div class="internal_popup_submit"><a style="background: #434343;margin-bottom: 5px;" class="hoverdiv345" href='./?site=<?php echo $currentFormloc; ?>&dopreffdo=<?php echo $_GET["dopref"]?>&csrf=<?php echo $csrf->get();?>'>Do Prefer</a><a href="./?site=<?php echo $currentFormloc; ?>"  class="hoverblackfontas">Cancel</a></div>		
		</div>
	</div>
<?php } ?>
<?php if($x = currentFormFunction($mysql, @$_GET["dopreffdo"])) { 
	if(!$csrf->check($_GET["csrf"])) { x_eventBoxPrep("CSRF Error - Try again!", "error", _COOKIES_); goto endof33ex; }
	$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET preferred = 0 WHERE LOWER(domain) = '".trim(strtolower($x["domain"]))."'");
	$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET preferred = 0 WHERE LOWER(domain) = '".trim(strtolower($x["domain"]))."'");
	$mysql->query("UPDATE ".$currentFormtable." SET preferred = 1 WHERE id = '".$x["id"]."'");
	$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET registered = 2 WHERE LOWER(domain) = '".strtolower($x["domain"])."'");
	$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET registered = 2 WHERE LOWER(domain) = '".strtolower($x["domain"])."'");
	x_eventBoxPrep("The selected Domain is now prefered in conflicts!", "ok", _COOKIES_);
} ?>
<?php if($x = currentFormFunction($mysql, @$_GET["nopref"])) { ?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Domain: <?php echo htmlspecialchars($x["domain"]); ?></div>
				You dont want to prefer this domain anymore in conflicts? <br />
			<div class="internal_popup_submit"><a style="background: #434343;margin-bottom: 5px;" class="hoverdiv345" href='./?site=<?php echo $currentFormloc; ?>&noprefdo=<?php echo $_GET["nopref"]?>&csrf=<?php echo $csrf->get();?>'>Do not Prefer</a><a href="./?site=<?php echo $currentFormloc; ?>"  class="hoverblackfontas">Cancel</a></div>		
		</div>
	</div>
<?php } ?>
<?php if($x = currentFormFunction($mysql, @$_GET["noprefdo"])) { 
	if(!$csrf->check($_GET["csrf"])) { x_eventBoxPrep("CSRF Error - Try again!", "error", _COOKIES_); goto endof33ex; }
	$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET preferred = 0 WHERE LOWER(domain) = '".strtolower($x["domain"])."'");
	$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET preferred = 0 WHERE LOWER(domain) = '".strtolower($x["domain"])."'");
	$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET registered = 2 WHERE LOWER(domain) = '".strtolower($x["domain"])."'");
	$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET registered = 2 WHERE LOWER(domain) = '".strtolower($x["domain"])."'");
	x_eventBoxPrep("The selected Domain is no more prefered in conflicts!", "ok", _COOKIES_);
 } ?>
<?php if($x = currentFormFunction($mysql, @$_GET["repoff"])) { ?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Domain: <?php echo htmlspecialchars($x["domain"]); ?></div>
				Do you want to Disable Replication for the current Domain to Slave Servers? <br />
				The Domain will not be synced to other slave servers if this is not activated!<br />
			<div class="internal_popup_submit"><a style="background: #434343;margin-bottom: 5px;" class="hoverdiv345" href='./?site=<?php echo $currentFormloc; ?>&repoffdo=<?php echo $_GET["repoff"]?>&csrf=<?php echo $csrf->get();?>'>Disable Replication</a><a href="./?site=<?php echo $currentFormloc; ?>"  class="hoverblackfontas">Cancel</a></div>		
		</div>
	</div>
<?php } ?>
<?php if($x = currentFormFunction($mysql, @$_GET["repoffdo"])) {
	if(!$csrf->check($_GET["csrf"])) { x_eventBoxPrep("CSRF Error - Try again!", "error", _COOKIES_); goto endof33ex; }
	$mysql->query("UPDATE ".$currentFormtable." SET set_no_replicate = 1 WHERE id = '".$x["id"]."'");
	x_eventBoxPrep("The selected Domain is no more replicated to slave servers!", "ok", _COOKIES_);
} ?>
<?php if($x = currentFormFunction($mysql, @$_GET["repon"])) { ?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Domain: <?php echo htmlspecialchars($x["domain"]); ?></div>
				Do you want to Enable Replication for the current Domain to Slave Servers?<br />
				This domain will then be synced to Slave servers, if this server is configures there as master server.<br />
			<div class="internal_popup_submit"><a style="background: #434343;margin-bottom: 5px;" class="hoverdiv345" href='./?site=<?php echo $currentFormloc; ?>&repondo=<?php echo $_GET["repon"]?>&csrf=<?php echo $csrf->get();?>'>Enable Replication</a><a href="./?site=<?php echo $currentFormloc; ?>"  class="hoverblackfontas">Cancel</a></div>		
		</div>
	</div>
<?php } ?>
<?php if($x = currentFormFunction($mysql, @$_GET["repondo"])) { 	
	if(!$csrf->check($_GET["csrf"])) { x_eventBoxPrep("CSRF Error - Try again!", "error", _COOKIES_); goto endof33ex; }
	$mysql->query("UPDATE ".$currentFormtable." SET set_no_replicate = 0 WHERE id = '".$x["id"]."'");
	x_eventBoxPrep("The selected Domain will be replicated to slave servers!", "ok", _COOKIES_);
 } 
 if($x = currentFormFunction($mysql, @$_GET["dodeldomd"])) { 	
	if(!$csrf->check($_GET["csrf"])) { x_eventBoxPrep("CSRF Error - Try again!", "error", _COOKIES_); goto endof33ex; }
	$mysql->query("DELETE FROM ".$currentFormtable." WHERE id = '".$x["id"]."'");
	x_eventBoxPrep("The selected Domain has been deleted!", "ok", _COOKIES_);
 } 
	endof33ex:

		echo '<div class="content_box">';
	$ar = $mysql->select("SELECT * FROM "._TABLE_LOG_." WHERE section = 'replication' ORDER BY id DESC", false);
	if($currentFormloc == "apidomains") {
		echo "<b>Slave Domains</b><br />Here you can see all domains, which have been replicated out of remote servers to this local server. The information you see here updates every time the cronjob sync.php has been executed. You can get some help about the status icons and more <a href='#showmethepicture'>here</a>...<br />";
		echo 'The current data you see has been last updated: <font color="lime">'.@$ar["creation"].'</font>';
	}
	if($currentFormloc == "binddomains") {
		echo "<b>Master Domains</b><br/>Here you can see Master Domains on this local system. You can get some help about the status icons and more <a href='#showmethepicture'>here</a>...<br />";
		echo 'The current data you see has been last updated: <font color="lime">'.@$ar["creation"].'</font>';
	}
		echo "<br clear='left'>";
		$curissue	=	mysqli_query($mysql->mysqlcon, "SELECT * FROM ".$currentFormtable." ORDER BY id DESC");
$run = false;
				echo "<div style='width: 30%;float:left;'>Status</div>";
				echo "<div style='width: 70%;float:left;'>Domain</div>";
			echo "<br clear='left' />"; 
		while ($curissuer	=	mysqli_fetch_array($curissue) ) { 
				if( @$curissuer["domain_type"] == "user" AND $currentFormloc == "binddomains") { continue; }
				if( @$curissuer["domain_type"] != "user" AND $currentFormloc == "userdomains") { continue; }
				if( @$curissuer["domain_type"] != "user" AND $currentFormloc == "alldomain") { continue; }
				echo "<hr>";
				$run = true;
				echo "<div style='max-width: 30%;width: 30%;float:left;'> "; 

				if($curissuer["registered"] == 1) {
					echo "<div  class='sysbutton' style='background: lime; color: black !important;margin: 5px; padding: 2px;' href='#'>Active</div> ";
				} elseif($curissuer["registered"] == 2) {
					echo "<div  class='sysbutton' style='background: yellow;color: black !important;margin: 5px; padding: 2px;' href='#'>Waiting for Cron</div> ";
				} elseif($curissuer["registered"] == 0 AND $curissuer["conflict"] == 1 AND $curissuer["preferred"] == 0) {
					$search = $mysql->select("SELECT * FROM "._TABLE_CONFLICT_." WHERE LOWER(domain) = '".trim(strtolower($curissuer["domain"]))."'");
					//$mysql->query("UPDATE "._TABLE_CONFLICT_." SET registered = 2 WHERE LOWER(domain) = '".trim(strtolower($curissuer["domain"]))."'");
					if(is_array($search) AND !is_numeric($search["solved"])) { echo "<div  class='sysbutton' style='background: yellow;color: black !important;margin: 5px; padding: 2px;' href='#'>Disabled</div> "; }
					else { echo "<div  class='sysbutton' style='background: red;color: white !important;margin: 5px; padding: 2px;' href='#'>Inactive</div> "; }
					
					
				} else {
					echo "<div  class='sysbutton' style='background: red;color: white !important;margin: 5px; padding: 2px;' href='#'>Inactive</div> ";
				}
				
				if($curissuer["conflict"] == 1 AND $curissuer["preferred"] == 1) {
					echo "<div  class='sysbutton' style='background: yellow; color: black !important;margin: 5px; padding: 2px;' href='#'>Solved Conflict</div> ";
				}

				if($curissuer["conflict"] == 1 AND $curissuer["preferred"] == 0) {
					$search = $mysql->select("SELECT * FROM "._TABLE_CONFLICT_." WHERE LOWER(domain) = '".trim(strtolower($curissuer["domain"]))."'");
					//$mysql->query("UPDATE "._TABLE_CONFLICT_." SET registered = 2 WHERE LOWER(domain) = '".trim(strtolower($curissuer["domain"]))."'");
					if(is_array($search) AND !is_numeric($search["solved"])) { echo "<div  class='sysbutton' style='background: yellow; color: black !important;margin: 5px; padding: 2px;' href='#'>Solved Conflict</div> "; }
					else { echo "<div  class='sysbutton' style='background: red;color: white !important;margin: 5px; padding: 2px;' href='#'>Conflicted</div> "; }
					
				}				
				
				if($curissuer["preferred"] == 1) {
					echo "<div  class='sysbutton' style='background: lightblue; color: black !important;margin: 5px; padding: 2px;' href='#'>Preferred</div> ";
				}
				
				if($curissuer["set_no_replicate"] == 1) {
					echo "<div  class='sysbutton' style='background: yellow; color: black !important;margin: 5px; padding: 2px;' href='#'>Replication Disabled</div> ";
				}		

				if($curissuer["zonecheck"] == 0) {
					echo "<div class='sysbutton' style='background: red; color: white !important;margin: 5px; padding: 2px;'>Zonedata Error</div> ";
				}					
			
				if($curissuer["oldzonefallback"] == 1) {
					echo "<div class='sysbutton' style='background: red; color: white !important;margin: 5px; padding: 2px;'>New Zonedata invalid<br />Using Failsafe...</div> ";
				}				
				
				echo "</div>";	
				if(isset($curissuer["domain_type"])) { 
				
				echo "<div style='max-width: 70%;width: 70%;float:left;'>".$curissuer["domain"]."<br />";	
				
					echo "<a  class='sysbutton' href='./?site=".$currentFormloc."&showcontent=".$curissuer["id"]."'>Zonedata</a> ";
					echo "<a  class='sysbutton' href='./?site=".$currentFormloc."&showdata=".$curissuer["id"]."'>Details</a> ";
					//echo "<a  class='sysbutton' href='./?site=".$currentFormloc."&dodeldom=".$curissuer["id"]."'>Delete (Fix)</a> ";
					if($curissuer["set_no_replicate"] != 1 AND isset($curissuer["domain_type"]))  { echo "<a  class='sysbutton' style='background: red;color: white;' href='./?site=".$currentFormloc."&repoff=".$curissuer["id"]."&csrf=".$csrf->get()."'>Disable Replication</a> "; }
					elseif(isset($curissuer["domain_type"]))  { echo "<a  class='sysbutton' style='background: lime;' href='./?site=".$currentFormloc."&repon=".$curissuer["id"]."&csrf=".$csrf->get()."'>Enable Replication</a> "; }
					if($curissuer["preferred"] == 1)  { echo "<a  class='sysbutton' style='background: red;color: white;' href='./?site=".$currentFormloc."&nopref=".$curissuer["id"]."&csrf=".$csrf->get()."'>Not Prefer Domain</a> "; }
					elseif($curissuer["preferred"] != 1)  { echo "<a  class='sysbutton' style='background: lime;' href='./?site=".$currentFormloc."&dopref=".$curissuer["id"]."&csrf=".$csrf->get()."'>Prefer Domain</a> "; } echo "<br clear='left'>";
					//else  { echo "<a  class='sysbutton' style='background: lime;' href='./?site=binddomains&repon=".$curissuer["id"]."'>Enable Replication</a> "; }				
				echo "</div><br clear='left'>";	
				
				} else { 
					echo "<div style='width: 70%;float:left;'>".$curissuer["domain"]."<br />"; 
					echo "<font style='font-size: 13px;'>Master-Server: ".dnshttp_server_get($mysql, $curissuer["fk_server"])["api_path"]."</font><br />"; 
					
					echo "<a  class='sysbutton' href='./?site=".$currentFormloc."&showcontent=".$curissuer["id"]."'>Zonedata</a> ";
					echo "<a  class='sysbutton' href='./?site=".$currentFormloc."&showdata=".$curissuer["id"]."'>Details</a> ";
					//echo "<a  class='sysbutton' href='./?site=".$currentFormloc."&dodeldom=".$curissuer["id"]."'>Delete (Fix)</a> ";
					if($curissuer["set_no_replicate"] != 1 AND isset($curissuer["domain_type"]))  { echo "<a  class='sysbutton' style='background: red;color: white;' href='./?site=".$currentFormloc."&repoff=".$curissuer["id"]."&csrf=".$csrf->get()."'>Disable Replication</a> "; }
					elseif(isset($curissuer["domain_type"]))  { echo "<a  class='sysbutton' style='background: lime;' href='./?site=".$currentFormloc."&repon=".$curissuer["id"]."&csrf=".$csrf->get()."'>Enable Replication</a> "; }
					if($curissuer["preferred"] == 1)  { echo "<a  class='sysbutton' style='background: red;color: white;' href='./?site=".$currentFormloc."&nopref=".$curissuer["id"]."&csrf=".$csrf->get()."'>Disable Prefer</a> "; }
					elseif($curissuer["preferred"] != 1)  { echo "<a  class='sysbutton' style='background: lime;' href='./?site=".$currentFormloc."&dopref=".$curissuer["id"]."&csrf=".$csrf->get()."'>Enable Prefer</a> "; } echo "<br clear='left'>";
					//else  { echo "<a  class='sysbutton' style='background: lime;' href='./?site=binddomains&repon=".$curissuer["id"]."'>Enable Replication</a> "; }
					echo "</div><br clear='left'>"; 
				
				}
				//echo "<div style='width: 20%;float:left;'>".$curissuer["modification"]."</div>";<?php echo $currentFormloc;




		} if(!$run) { echo "Currently there are no domains...! x(";}
		
	echo '</div>';		
		
?>		<div class="content_box" style="text-align: left;" id="showmethepicture"><b>Some Explanations</b><br /><br />
		<b>Status Icon Informations:</b><br />
		The status of a server is updated if the cronjob <b>sync.php</b> does execute. It may be good to take a look at this values from time to time, but more important is to take a look at the replications log. All necessarry informations you need to know about the replication are written there. (The Replication Protocol Section unter the tab Replication).<br />

			
		<br /><b>Domain Status Icons:</b><br />			
			<div  class='sysbutton' style='background: lime; color: black !important;margin: 5px; padding: 2px;max-width: 200px;' href='#'>Active</div> If label above is shown, the Domain is active and registered with bind!
			
			
			<br clear="left">
			<div  class='sysbutton' style='background: red;color: white !important;margin: 5px; padding: 2px;max-width: 200px;' href='#'>Inactive</div> If lavel above is dhown, the Domain is not registered with bind and not active, this can be due to an error and maybe should been checked.. ;_; (But it is just saying, that this domain is not used in bind currently, and not able to query at the server. If you have no problem with that, leave it like that is.
			
			
			
			<br clear="left">
			<div  class='sysbutton' style='background: yellow;color: black !important;margin: 5px; padding: 2px;max-width: 200px;' href='#'>Waiting for Cron</div> If you see lavel above, the domain is waiting for the next cronjob run to update its status!
			
			<br clear="left">
			<div  class='sysbutton' style='background: yellow;color: black !important;margin: 5px; padding: 2px;max-width: 200px;' href='#'>Disabled</div> If you see the label above, this domain had been in a conflict which has been solved, by choosing another domain as preferred!
			
			<br clear="left">
		<br /><b>Conflict Icons:</b><br />		
			<div  class='sysbutton' style='background: red;color: white !important;margin: 5px; padding: 2px;max-width: 200px;' href='#'>Conflicted</div> If label above is shown on domain, it is inactive, because if has a conflict (duplicated domain on another slave server or at this master server). This domain will not be registered to the local system, until the conflict has been solved by preferring a domain via this interface.
			<br clear="left">
			
			<div  class='sysbutton' style='background: yellow; color: black !important;margin: 5px; padding: 2px;max-width: 200px;' href='#'>Solved Conflict</div> If label above is shown, the domain has a conflict and is duplicated on this servers domain table. But this conflict has been solved by choosing a prefered domain! So all is alright and no steps are required, you can see more information about conflicts and if they are solved in the "Conflicts" Area...
			<br clear="left">
			
			
		<br /><b>Domain Info Icons:</b><br />	
			<div  class='sysbutton' style='background: lightblue; color: black !important;margin: 5px; padding: 2px; max-width: 200px;' href='#'>Preferred</div> If label above is shown on domain, this domain is preferred and will be used if there are any conflicts for this specific domain!
			<br clear="left">
			
			<div  class='sysbutton' style='background: yellow; color: black !important;margin: 5px; padding: 2px; max-width: 200px;' href='#'>Replication Disabled</div> If you see label above on a domain, this domain has been set to "not replicate". This master domain on this local server will than not be replicated to other remote servers, if they are asking for the locals server domains list...
			<br clear="left">
			
		<br /><b>Zonedata Status Icons:</b><br />
			<div class='sysbutton' style='background: red; color: white !important;margin: 5px; padding: 2px; max-width: 200px;'>New Zonedata invalid<br />Using Failsafe...</div> If this label above is shown, there is an error in the domains content data and it has not been registered with bind.<br clear="left">
			<div class='sysbutton' style='background: red; color: white !important;margin: 5px; padding: 2px; max-width: 200px;'>Zonedata Error</div> If label above is shown, the domain has a zonedata error on the new domains zonedata, the old (functioning) zonefile will than be used, until the new zonedata is valid...<br clear="left">
		</div></div>


<?php
		
		
		
		
	

}
?>	