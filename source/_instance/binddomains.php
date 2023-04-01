<?php
if(!$permsobj->hasPerm($user->user_id, "perm_domains_bind") AND $user->user_rank != 0) { echo "<div class='content_box'>You do not have Permission!</div>"; } else {	
	
		echo '<div class="content_box">';
				echo "<div style='width: 50%;float:left;'>Domain</div>";
				echo "<div style='width: 20%;float:left;'>Updated</div>";
				echo "<div style='width: 30%;float:left;'>Actions</div><br clear='left' />";

		
		$curissue	=	mysqli_query($mysql->mysqlcon, "SELECT * FROM "._TABLE_DOMAIN_BIND_." ORDER BY id DESC");

		while ($curissuer	=	mysqli_fetch_array($curissue) ) { 
				echo "<hr>";
				echo "<div style='width: 50%;float:left;'>".$curissuer["domain"]."</div>";	
				echo "<div style='width: 20%;float:left;'>".$curissuer["modification"]."</div>";

				echo "<div style='width: 30%;float:left;'>";	
					echo "<a  class='sysbutton' href='./?site=binddomains&show=".$curissuer["id"]."'>Content</a> ";
				echo "</div><br clear='left' />";					
		}
	
	echo '</div>';
?>	

<?php if(dnshttp_bind_get($mysql, @$_GET["show"])) { ?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Sessions: <?php echo $user->get($_GET["session"])["user_name"]; ?></div>
			
			<?php
		
		$curissue	=	mysqli_query($mysql->mysqlcon, "SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE id = '".$_GET["show"]."' ORDER BY id DESC"); 

		while ($curissuer	=	mysqli_fetch_array($curissue) ) { 
				echo "<textarea style='background: rgba(0,0,0,0.9); color: white; width: 100%;max-width: 100%;max-height: 40vh;height: 40vh;' readonly>".$curissuer["content"]."</textarea><br />";	
		} ?>
			
			<div class="internal_popup_submit"><a href="./?site=binddomains">Cancel</a></div>		
		</div>
	</div>
<?php }} ?>