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
		Bugfish Framework Codebase // MIT License
		// Autor: Jan-Maurice Dahlmanns (Bugfish)
		// Website: www.bugfish.eu 
	*/
	class x_class_var {
		// Class Variables
		private $variable_msqlcon   = false; 
		private $variable_table     = false; 
		private $db_r_c_title   	= "descriptor"; 
		private $db_r_c_value   	= "value";
		private $db_r_c_descr   	= "description";
		private $db_r_c_id   		= "id";
		private $db_r_c_section 	= "";
		private $sections_name   	= ""; 
		// Table Initialization
		private function create_table() {
			return $this->variable_msqlcon->query("CREATE TABLE IF NOT EXISTS `".$this->variable_table."` (
												  `".$this->db_r_c_id."` int(9) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
												  `".$this->db_r_c_title."` varchar(256) NOT NULL COMMENT 'Descriptor for Constant',
												  `".$this->db_r_c_value."` text COMMENT 'Value for Constant',
												  `".$this->db_r_c_descr."` text COMMENT 'Description for Constant',
												  `".$this->db_r_c_section."` varchar(128) DEFAULT '' COMMENT 'Section for Constant (For Multi Site)',
												  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date of Entry | Will be Auto-Set',
												  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date of Entry with Auto-Update on Change',
												  PRIMARY KEY (`id`),
												  UNIQUE KEY `x_class_var_unique` (`section`,`descriptor`) USING BTREE);");}
		// Construct
		function __construct($mysql, $tablename, $section, $descriptor = "descriptor", $value = "value", $description = "description", $sectionfield = "section", $idfield = "id") { 
			if (session_status() !== PHP_SESSION_ACTIVE) {@session_start();}
			$this->variable_msqlcon = $mysql; 
			$this->db_r_c_title     = @substr(trim($descriptor), 0, 255); 
			$this->db_r_c_value     = @substr(trim($value), 0, 255); 
			$this->db_r_c_descr     = @substr(trim($description), 0, 255); 
			$this->db_r_c_section   = @substr(trim($sectionfield), 0, 255); 
			$this->db_r_c_id    	= @substr(trim($idfield), 0, 255); 
			$this->sections_name    = @substr(trim($section), 0, 127); 
			$this->variable_table   = $tablename; 
			if(!$this->variable_msqlcon->table_exists($tablename)) { $this->create_table(); $this->variable_msqlcon->free_all();  } }
			

		// Init as Constant
		public function init_constant(){ 
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->sections_name;
			if(!$this->db_r_c_section) { $section = ""; } else { $section = " WHERE ".$this->db_r_c_section." = ? ";}
			$rres = @$this->variable_msqlcon->select("SELECT * FROM `".$this->variable_table."` ".$section, true, $b);
			if(is_array($rres)) {
				foreach($rres AS $key => $value) {	
					if(!defined($value[$this->db_r_c_title])) { 
						define($value[$this->db_r_c_title], $value["".$this->db_r_c_value.""]);
					}	
				}
			} return true;
		}
		
		// Init as Array
		public function get_array(){ 
			$tmparray = array();
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->sections_name;	
			$section = " WHERE ".$this->db_r_c_section." = ? ";
			$rres = @$this->variable_msqlcon->select("SELECT * FROM `".$this->variable_table."` ".$section, true, $b);
			if(is_array($rres)) {
				foreach($rres AS $key => $value) {	
					$tmparray_two = array();
					$tmparray_two[$this->db_r_c_title] = $this->db_r_c_value;
					array_push($tmparray, $tmparray_two);
				}
			} return $tmparray;
		}
		
		// Init as Array
		public function get_array_full(){ 
			$tmparray = array();
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->sections_name;	
			$section = " WHERE ".$this->db_r_c_section." = ? ";
			$rres = @$this->variable_msqlcon->select("SELECT * FROM `".$this->variable_table."` ".$section, true, $b);
			return $rres;
		}
		
		// Get a Full Array from Row of Table with This name Found 1st Hit
		public function get_full($name) {
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->sections_name;	
			$b[1]["type"]	=	"s";
			$b[1]["value"]	=	$name;	
			return @$this->variable_msqlcon->select("SELECT * FROM `".$this->variable_table."` WHERE (".$this->db_r_c_section." = ? ) AND ".$this->db_r_c_title." = ?;", false, $b);							
		}			
		
		// Check if Var Exists
		public function exists($name) { if($this->get_id($name)) { return true; } else { return false; }}		
		// Get a Variable
		public function get($name) { $var = $this->get_full($name); if(isset($var[$this->db_r_c_id])) { return $var[$this->db_r_c_value]; } else { return false; }	}			
		private function get_id($name) { $var = $this->get_full($name); if(isset($var[$this->db_r_c_id])) { return $var[$this->db_r_c_id]; } else { return false; }	}			
		
		// Delete a Constant
		public function del($name) {
			if($var = $this->get_full($name)) {
				return @$this->variable_msqlcon->query("DELETE FROM `".$this->variable_table."` WHERE ".$this->db_r_c_id." = ".$var[$this->db_r_c_id]." ;");	
			} else {return false;}}				
		
		// Setup Variable
		public function setup($name, $value, $description = "") { if(!$this->exists($name)) { return $this->set($name, $value, $description, true, false); } else { return false; }}			
		
		// Add Variable
		public function add($name, $value, $description = "", $overwrite = false)  { return $this->set($name, $value, $description, true, $overwrite);}
	
		// Internal Function to Handle Variables
		public function set($name, $value, $description = false, $add = true, $overwrite = true) {
			if(!$description OR !$this->db_r_c_descr) { $descriptionedit = false; $descriptioneditv = ""; } else { $descriptionedit = true ;$descriptioneditv = $description;}
			
			if($this->exists($name)) { 
				if($overwrite) {	
					if($descriptionedit) {
						$b[0]["type"]	=	"s";
						$b[0]["value"]	=	$descriptioneditv;		
						$b[1]["type"]	=	"s";
						$b[1]["value"]	=	$value;
						$b[2]["type"]	=	"s";
						$b[2]["value"]	=	$name;
						$b[3]["type"]	=	"s";
						$b[3]["value"]	=	$this->sections_name;
						return @$this->variable_msqlcon->update("UPDATE `".$this->variable_table."` SET ".$this->db_r_c_descr." = ?, ".$this->db_r_c_value." = ? WHERE ".$this->db_r_c_title." = ? AND (".$this->db_r_c_section." = ?);", $b); 														
					} else {
						$b[0]["type"]	=	"s";
						$b[0]["value"]	=	$value;
						$b[1]["type"]	=	"s";
						$b[1]["value"]	=	$name;
						$b[2]["type"]	=	"s";
						$b[2]["value"]	=	$this->sections_name;
						return @$this->variable_msqlcon->update("UPDATE `".$this->variable_table."` SET ".$this->db_r_c_value." = ? WHERE ".$this->db_r_c_title." = ? AND (".$this->db_r_c_section." = ?);", $b); 								
								
					}
				} return false;
			} else {
				if($add) {
					if($descriptionedit) {
						$b[0]["type"]	=	"s";
						$b[0]["value"]	=	$name;
						$b[1]["type"]	=	"s";
						$b[1]["value"]	=	$value;
						$b[2]["type"]	=	"s";
						$b[2]["value"]	=	$descriptioneditv;
						$b[3]["type"]	=	"s";
						$b[3]["value"]	=	$this->sections_name;
						return @$this->variable_msqlcon->query("INSERT INTO `".$this->variable_table."`(".$this->db_r_c_title.", ".$this->db_r_c_value.", ".$this->db_r_c_descr.", ".$this->db_r_c_section.") VALUES(?, ?, ?, ?);", $b);							
						
					} else {	
						$b[0]["type"]	=	"s";
						$b[0]["value"]	=	$name;
						$b[1]["type"]	=	"s";
						$b[1]["value"]	=	$value;
						$b[2]["type"]	=	"s";
						$b[2]["value"]	=	$this->sections_name;
						return @$this->variable_msqlcon->query("INSERT INTO `".$this->variable_table."`(".$this->db_r_c_title.", ".$this->db_r_c_value.", ".$this->db_r_c_section.") VALUES(?, ?, ?);", $b);							
						
					}
				} return false;
			} 
			return false;
		}		

		// Form new CSRF
		public function form_start($precookie = "") {
			$_SESSION[$precookie."x_class_var"] = mt_rand(10000000, 90000000);
			if(!is_numeric(@$_SESSION[$precookie."x_class_var_csrf"])) { $_SESSION[$precookie."x_class_var_csrf"] = mt_rand(10000000, 90000000); }
		}
		public function form_end($precookie = "") {
			$_SESSION[$precookie."x_class_var_csrf"] = $_SESSION[$precookie."x_class_var"];
		}
		
		
		// Setup Int
		public function form($varname, $type = "int", $selectarray = array(), $precookie = "", $button_class="btn btn-warning waves-effect waves-light", $itemclass = "form-control", $editbuttonname = "Edit") {	
			$outputform = 0;
			if(!$this->exists($varname)) { return "error-var-not-found";}
			if(!isset($_SESSION[$precookie."x_class_var"])) {$_SESSION[$precookie."x_class_var"] = mt_rand(10000000, 90000000); }
			$tmp_var = $this->get_full($varname);
			$varnamenew = $precookie."x_class_var_submit_".$tmp_var[$this->db_r_c_id];
			$varnamenews = $precookie."x_class_var_submit_val".$tmp_var[$this->db_r_c_id];
			if(isset($_POST[$varnamenew])) {
				if($_POST[$precookie."x_class_var_csrf"] == $_SESSION[$precookie."x_class_var_csrf"]) {
					$finalvalue = false;
					switch($type) {
						case "int": $finalvalue = @$_POST[$varnamenews]; break;	
						case "text": $finalvalue = @$_POST[$varnamenews]; break;	
						case "string": $finalvalue = @$_POST[$varnamenews]; break;	
						case "select": $finalvalue = @$_POST[$varnamenews]; break;		
						//case "bool": if(@$_POST[$varnamenews]) {$finalvalue =1;} else {$finalvalue =0;}  break;		
					} 		
					if(@$_POST[$varnamenews] != $tmp_var[$this->db_r_c_value]) {
						if($this->set($varname, $finalvalue, false, true, true)) {
							$text = "<div class='x_class_var_change_ok'>Changed successfully!</div>";
						} else {$text = "<div class='x_class_var_change_fail'>Could not be changed!</div>";}	
					} else {$text = "<div class='x_class_var_change_ok'>Changed successfully!</div>";}	
				} else { $text = "<div class='x_class_var_change_fail'>CSRF Error Try Again!</div>"; }
			} $current = $this->get_full($varname);  ?>
			<section id="<?php echo $precookie; ?>x_class_var_anchor_<?php echo $current[$this->db_r_c_id]; ?>"></section><br />
			<div class="x_class_var" >
				<form method="post" action="#<?php echo $precookie; ?>x_class_var_anchor_<?php echo $current[$this->db_r_c_id]; ?>">
					<?php if(is_string($current[$this->db_r_c_title])) { echo "<div class='x_class_var_setup_title'>".$current[$this->db_r_c_title].""; echo "</div>"; } ?>
					<?php if(is_string($current[$this->db_r_c_descr])) { echo "<div class='x_class_var_setup_descr'>".$current[$this->db_r_c_descr]; echo "</div>"; } ?>
					<?php if(is_string(@$text) AND strlen(@$text) > 5) { echo @$text; echo ""; } ?>
						<!-- Int -->
						<?php if($type == "int") { ?> <input class="<?php echo $itemclass; ?>"  type="number" value="<?php if(is_array($current)) { echo @htmlentities($current[$this->db_r_c_value]); } ?>" name="<?php echo $varnamenews; ?>"><br /><?php } ?>				
						<!-- String -->
						<?php if($type == "string") { ?> <input class="<?php echo $itemclass; ?>"  type="text" value="<?php if(is_array($current)) { echo @htmlentities($current[$this->db_r_c_value]); } ?>" name="<?php echo $varnamenews; ?>"><br /><?php } ?>					
						<!-- Text -->
						<?php if($type == "text") { ?> <textarea class="<?php echo $itemclass; ?>"  name="<?php echo $varnamenews; ?>"><?php if(is_array($current)) { echo @htmlspecialchars($current[$this->db_r_c_value]); } ?></textarea><br /><?php } ?>
						<!-- Bool -->
						<?php if(false AND is_array($current) AND $current[$this->db_r_c_value] == 1) { $xxx = "checked"; } else { $xxx = ""; } ?>	
						<?php if(false) { ?>Configure: <input class="<?php echo $itemclass; ?>" type="checkbox" name="<?php echo $varnamenews; ?>" <?php echo $xxx; ?>><?php } ?>		
						<!-- Select -->
						<?php if($type == "select") { ?>
							<select class="<?php echo $itemclass; ?>"  name="<?php echo $varnamenews; ?>">
							<option value="<?php echo htmlentities($current[$this->db_r_c_value]); ?>"><?php 
								$nochange = @htmlentities($current[$this->db_r_c_value]);
								if(is_array($selectarray)) {
									foreach($selectarray AS $kk => $vv) {
										if(is_array($vv)) {
											if($vv[1] == $current[$this->db_r_c_value]) {
												$nochange = @htmlentities($vv[0]);
											}
										}
									}
								}
								echo $nochange; 
							
							?></option>
								<?php
									 foreach($selectarray AS $key => $value) { if(is_array($value)) {
										echo '<option value="'.$value[1].'">'.$value[0]."</option>";
									} else {
											echo '<option value="'.$value.'">'.$value."</option>";	
									} }
								?>
							</select><br />
						<?php } ?>
						<!-- Misc Form -->
						<input type="hidden" value="<?php echo $_SESSION[$precookie."x_class_var"]; ?>" name="<?php echo $precookie; ?>x_class_var_csrf">
						<input type="hidden" value="<?php echo "true"; ?>" name="<?php $varnamenew; ?>">
						<input type="submit" value="<?php echo $editbuttonname; ?>" name="<?php echo $varnamenew; ?>" class="<?php echo $button_class; ?>">			
				</form>
			</div>
		<?php  return $outputform; }	
	}
