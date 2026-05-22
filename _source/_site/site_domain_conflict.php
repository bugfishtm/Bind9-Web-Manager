<?php 
	/* 	
		.........%%%%%...%%..%%...%%%%...%%..%%..%%%%%%..%%%%%%..%%%%%..
		.........%%..%%..%%%.%%..%%......%%..%%....%%......%%....%%..%%.
		.........%%..%%..%%.%%%...%%%%...%%%%%%....%%......%%....%%%%%..
		.........%%..%%..%%..%%......%%..%%..%%....%%......%%....%%.....
		.........%%%%%...%%..%%...%%%%...%%..%%....%%......%%....%%.....
		................................................................
					PHP DNS Software by Jan-Maurice "Bugfish" Dahlmanns
	*/

	#	Copyright (C) 2026 Jan Maurice Dahlmanns [Bugfish]

	#	This program is free software: you can redistribute it and/or modify
	#	it under the terms of the GNU General Public License as published by
	#	the Free Software Foundation, either version 3 of the License, or
	#	(at your option) any later version.

	#	This program is distributed in the hope that it will be useful,
	#	but WITHOUT ANY WARRANTY; without even the implied warranty of
	#	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	#	GNU General Public License for more details.

	#	You should have received a copy of the GNU General Public License
	#	along with this program.  If not, see <https://www.gnu.org/licenses/>.
	
	/*************************************************************************
		Disable Hardlinking
	*************************************************************************/
	if(!defined("_SQL_USER_")) { @http_response_code(404); Header("Location: ../"); exit(); }
	
	/*************************************************************************
		Include Header
	*************************************************************************/
	define("_SUB_PAGE_TITLE_", "Conflicts");
	require_once("./_default/default_header.php");
	require_once("./_default/default_navigation.php");
	echo '<div style="margin: 0px 0px 0px 0px; padding: 10px; 10px; 10px; 10px;"></div>'; ?>	
	
	<!----------------- Show Alert Boxes -------------------->
    <div class="callout callout-info mb-3" role="alert">This view displays duplicate domains that may cause conflicts. To resolve them, open the domain details and set your preferred domain accordingly.<br /><br />
		Please note that this overview may not reflect the current state, as it is updated based on your cronjob execution interval. The default interval is every 3 hours, but can be adjusted during server installation as described in the documentation. The timestamp of the last completed cronjob execution is available in the Cronjob Debugging section.</div>

	<!----------------- Table Element  ---------------->
	<div class="card mb-4">
	  <div class="card-header">
		<h3 class="card-title">Conflicts</h3>
	  </div>
	  <!-- /.card-header  -->
	  <div class="card-body p-2">
			<?php
				$curissue	=	mysqli_query($mysql->mysqlcon, "SELECT * FROM "._TABLE_CONFLICT_." ORDER BY id DESC");
				$run = false;
				while ($curissuer	=	mysqli_fetch_array($curissue) ) {
					$run = true;
					$string = "";
					if($curissuer["solved"] == 0 AND is_numeric($curissuer["solved"])) { 	
						echo '<div class="callout callout-danger mb-3" role="alert">';
						echo 'Conflicted Domain: <b>'.htmlspecialchars(@$curissuer["domain"] ?? '').'</b><br /><br /><b>Conflicts</b>: <div style="font-weight: normal !important;">'.@dnshttp_conflicts_helper($curissuer["servers"]).'</div> ';	
					} else { 
						echo '<div class="callout callout-success mb-3" role="alert">';
						echo 'Conflicted Domain: <b>'.htmlspecialchars(@$curissuer["domain"] ?? '').'</b><br /><br /><b>Conflicts</b>:<br /> '.@dnshttp_conflicts_helper($curissuer["servers"]).'<br /><b>Solved with</b>: ';	
						echo $curissuer["solved"]; 
					}
					echo '</div>';	
				}
				if(!$run) {
					?>
						<div class="callout callout-success mb-3" role="alert">Congratulations, no conflicts have been found!</div>
					<?php
				}
					
			?>
	  </div>
	</div>
	
	<?php
	/*************************************************************************
		Include Footer
	*************************************************************************/
	require_once("./_template/tpl_search.php");
	require_once("./_default/default_footer.php");