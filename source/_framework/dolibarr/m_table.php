<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ Dolibarr Table Set */	
	// Print a Simple Table
	function m_table_simple($title, $array, $titlelist, $tableid = "", $alignarray = false, $imgeforlist = 'generic'){
		$colspan	=	count($titlelist);
		print_barre_liste($title, NULL, $_SERVER["PHP_SELF"], NULL, NULL, NULL, NULL, NULL, NULL, $imgeforlist);
		print '<table class="tagtable liste" id="mtsimple_'.$tableid.'"><tr class="liste_titre">';
		$t_r_count	=	0;
		foreach( $titlelist as $key => $value ){
				if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				//print '<th class="liste_titre" style="text-align: '.$t_align.';">'.$value.'</th>';
				print_liste_field_titre($value, $_SERVER["PHP_SELF"], NULL, NULL, NULL, "style='text-align: ".$t_align.";'");
				$t_r_count	=	$t_r_count	+ 1;}
		print '</tr>';
		if(empty($array)) {print '<tr class="oddeven"><td colspan="'.$colspan.'" style="text-align: center"><i>Keine Daten vorhanden...</i></td></tr>';} else {
			foreach( $array as $key => $value ){
				print '<tr class="oddeven">';
				$t_r_count	=	0;
				foreach( $array[$key] as $key1 => $value1 ){
				if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				print '<td style="text-align: '.$t_align.';">'.$value1.'</td>';
				$t_r_count	=	$t_r_count	+ 1;}				
			print '</tr>';}}
		print '</table>';}
		
		
	// Table with Search Function
	function m_table_complex($title, $array, $titlelist, $formid = "", $alignarray = false, $imgeforlist = "generic"){
		$colspan	=	count($titlelist);
		print '<form method="post"  id="mtcomplex_'.$formid.'"><input type="submit" style="display:none;">';
		print_barre_liste($title, NULL, $_SERVER["PHP_SELF"], NULL, NULL, NULL, NULL, NULL, NULL, $imgeforlist);
		print '<table class="tagtable liste">';
			if(!empty($array)) {
				print '<tr class="liste_titre">';$tcount	=	0;
				foreach( $array[0] as $key => $value ){
					$tmp_placeholder = $titlelist[$tcount];$tcount = $tcount + 1;
					$tmp_value = @htmlspecialchars($_POST['mtc_'.$key]);
					print '<th><input type="text" name="mtc_'.$key.'" value="'.@$tmp_value.'" placeholder="'.$tmp_placeholder.'">';
						if(!empty($tmp_value)) { echo '<br />Active Search:<br /><font size="-1">'.$tmp_value.'</font>'; }
						print '</th>';					
					}
				print '</tr>';}
						print '<tr class="liste_titre">';$t_r_count	= 0 ;
			foreach( $titlelist as $key => $value ){
				if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				//print '<th class="liste_titre" style="text-align: '.$t_align.';">'.$value.'</th>';
				print_liste_field_titre($value, $_SERVER["PHP_SELF"], NULL, NULL, NULL, "style='text-align: ".$t_align.";'");
				$t_r_count	=	$t_r_count	+ 1;}print '</tr>';
		if(empty($array)) {print '<tr class="oddeven"><td colspan="'.$colspan.'" style="text-align: center"><i>Keine Daten vorhanden...</i></td></tr>'; } else {
			$didfound = false;
			foreach( $array as $key => $value ){
					$search_relevant	=	true;
					foreach( $array[$key] as $key1 => $value1 ){
						if(isset($_POST["mtc_".$key1]) AND @trim($_POST["mtc_".$key1]) != "") {if(strpos($value1, $_POST["mtc_".$key1]) <= -1) {$search_relevant	=	false;}}}							
				if($search_relevant) {
					print '<tr class="oddeven">';
					$t_r_count	=	0;
					foreach( $array[$key] as $key1 => $value1 ){
						if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						print '<td style="text-align: '.$t_align.';">'.$value1.'</td>';
						$t_r_count	=	$t_r_count	+ 1;$didfound = true;}print '</tr>';}}
			if(!$didfound) {print '<tr class="oddeven"><td colspan="'.$colspan.'" style="text-align: center"><i>Keine Daten vorhanden...</i></td></tr>';}}
		print '</table></form>';}	
