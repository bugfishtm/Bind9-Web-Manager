<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ X Table Library	*/
	// Table with Search Function
	function x_table_div($array, $titlelist, $alignarray = false, $percentarray = false, $title = false){
		$colspan	=	count($titlelist);
		print '<div class="x_table_div">';if(is_string($title)) {echo '<div class="x_table_div_title">'.$title.'</div>';}
		$t_r_count	= 0 ;$xcc = 0;
		echo '<div class="x_table_div_titles">';
			foreach( $titlelist as $key => $value ){
				if(is_array($percentarray)) { $t_pct	=	$percentarray[$t_r_count]; } else {$t_pct	=	"10%"; }
				if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				print '<section class="x_table_div_titles_title" style="float:left;text-align: '.$t_align.';width: '.$t_pct.';">'.$value.'</section>';
				$t_r_count	=	$t_r_count	+ 1;}print "<br clear='left'/></div>";
		if(empty($array)) {print '<div class="x_table_div_row" style="width: 100%;"><i>Keine Daten vorhanden...</i></div>'; } else {
			foreach( $array as $key => $value ){
					$t_r_count	=	0; if($xcc % 2 == 0) { print '<div class="x_table_div_frow x_table_div_frow_even">';} else {print '<div class="x_table_div_frow x_table_div_frow_odd">';}
					foreach( $array[$key] as $key1 => $value1 ){
						if(is_array($percentarray)) { $t_pct	=	$percentarray[$t_r_count]; } else {$t_pct	=	"10%"; }
						if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						print '<div class="x_table_div_row" style="float:left;text-align: '.$t_align.';width: '.$t_pct.';">'.$value1.'</div>';
						$t_r_count	=	$t_r_count	+ 1;$xcc = $xcc  + 1;}print "<br clear='left'/></div>";}}
		print '</div>';}	

	// Table with Search Function
	function x_table_complex($array, $titlelist, $formid = "", $alignarray = false){
		$colspan	=	count($titlelist);
		print '<form method="post"  id="x_table_complex_'.$formid.'"><input type="submit" style="display:none;">';
		print '<table class="x_table_complex"><tr class="x_table_complex_tr"></tr>';
			if(!empty($array)) {
				print '<tr class="x_table_complex_tr">';$tcount	=	0;
				foreach( $array[0] as $key => $value ){
					$tmp_placeholder = $titlelist[$tcount];$tcount = $tcount + 1;
					$tmp_value = @htmlspecialchars($_POST['x_t_c_'.$key]);
					print '<th><input type="text" name="x_t_c_'.$key.'" value="'.@$tmp_value.'" placeholder="'.$tmp_placeholder.'"></th>';}
				print '</tr>';}
		print '<tr class="x_table_complex_tr">';$t_r_count	= 0 ;
			foreach( $titlelist as $key => $value ){
				if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				print '<th class="x_table_complex_th" style="text-align: '.$t_align.';">'.$value.'</th>';
				$t_r_count	=	$t_r_count	+ 1;}print '</tr>';
		if(empty($array)) {print '<tr class="x_table_complex_tr"><td colspan="'.$colspan.'" style="text-align: center"><i>Keine Daten vorhanden...</i></td></tr>'; } else {
			$didfound = false;
			foreach( $array as $key => $value ){
					$search_relevant	=	true;
					foreach( $array[$key] as $key1 => $value1 ){
						if(isset($_POST["x_t_c_".$key1]) AND @trim($_POST["x_t_c_".$key1]) != "") {if(strpos($value1, $_POST["x_t_c_".$key1]) <= -1) {$search_relevant	=	false;}}}							
				if($search_relevant) {
					print '<tr class="x_table_complex_tr">';
					$t_r_count	=	0;
					foreach( $array[$key] as $key1 => $value1 ){
						if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						print '<td style="text-align: '.$t_align.';">'.$value1.'</td>';
						$t_r_count	=	$t_r_count	+ 1;$didfound = true;}print '</tr>';}}
			if(!$didfound) {print '<tr class="x_table_complex_tr"><td colspan="'.$colspan.'" style="text-align: center"><i>Keine Daten vorhanden...</i></td></tr>';}}
		print '</table></form>';}	

	/*	Simple Table Function */
	function x_table_simple($array, $titlelist, $tableid = "x_table_simple", $alignarray = false){
		$colspan	=	count($titlelist);
		print '<table class="x_table_simple" id="'.$tableid.'"><tr class="x_table_simple_tr"><td colspan="'.$colspan.'"></td></tr><tr class="x_table_simple_trhead">';
		$t_r_count	=	0;
		foreach( $titlelist as $key => $value ){
				if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				print '<th class="x_table_simple_th" style="text-align: '.$t_align.';">'.$value.'</th>';$t_r_count	=	$t_r_count	+ 1;}
		print '</tr>';
		if(empty($array)) {print '<tr class="x_table_simple_tr"><td colspan="'.$colspan.'" style="text-align: center"><i>Keine Daten vorhanden...</i></td></tr>';} else {
			foreach( $array as $key => $value ){
				print '<tr class="x_table_simple_tr">';
				$t_r_count	=	0;
				foreach( $array[$key] as $key1 => $value1 ){
				if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				print '<td style="text-align: '.$t_align.';">'.$value1.'</td>';
				$t_r_count	=	$t_r_count	+ 1;}				
			print '</tr>';}}
		print '</table>';}
