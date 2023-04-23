<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/  Variables Control Class */
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
												  `id` int(9) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
												  `descriptor` varchar(256) NOT NULL COMMENT 'Descriptor for Constant',
												  `value` text COMMENT 'Value for Constant',
												  `description` text COMMENT 'Description for Constant',
												  `section` varchar(128) DEFAULT '' COMMENT 'Section for Constant (For Multi Site)',
												  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date of Entry | Will be Auto-Set',
												  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date of Entry with Auto-Update on Change',
												  PRIMARY KEY (`id`),
												  UNIQUE KEY `Unique` (`section`,`descriptor`) USING BTREE);");}
		// Construct
		function __construct($mysql, $tablename, $section = "", $descriptor = "descriptor", $value = "value", $description = "description", $sectionfield = "section", $idfield = "id") { 
			if (session_status() !== PHP_SESSION_ACTIVE) {@session_start();}
			$this->variable_msqlcon = $mysql; 
			$this->db_r_c_title     = @substr(trim($descriptor), 0, 255); 
			$this->db_r_c_value     = @substr(trim($value), 0, 255); 
			$this->db_r_c_descr     = @substr(trim($description), 0, 255); 
			$this->db_r_c_section   = @substr(trim($sectionfield), 0, 255); 
			$this->db_r_c_id    	= @substr(trim($sectionfield), 0, 255); 
			$this->sections_name    = @substr(trim($section), 0, 127); 
			$this->variable_table   = $tablename; 
			if(!$this->variable_msqlcon->table_exists($tablename)) { $this->create_table(); $this->variable_msqlcon->free_all();  } }
			

		// Init as Constant
		public function init_constant(){ 
			if(!$this->db_r_c_section) { $section = ""; } else { $section = " WHERE ".$this->db_r_c_section." = '".$this->sections_name."' ";}
			$rres = @$this->variable_msqlcon->select("SELECT * FROM ".$this->variable_table." ".$section, true);
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
			if(!$this->db_r_c_section) { $section = ""; } else { $section = " WHERE ".$this->db_r_c_section." = '".$this->sections_name."' ";}
			$rres = @$this->variable_msqlcon->select("SELECT * FROM ".$this->variable_table." ".$section, true);
			if(is_array($rres)) {
				foreach($rres AS $key => $value) {	
					$tmparray_two = array();
					$tmparray_two[$this->db_r_c_title] = $this->db_r_c_value;
					array_push($tmparray, $tmparray_two);
				}
			} return $tmparray;
		}
		
		// Add Variable
		public function add($name, $value, $description = false, $overwrite = false)  { return $this->set($name, $value, $description, true, $overwrite);}
		// Setup Variable
		public function setup($name, $value, $description = false) { return $this->set($name, $value, $description, true, false);}		
		// Check if Var Exists
		public function exists($name) { if($this->get($name)) { return true; } else { return false; }}		
		// Get a Variable
		public function get($name) { $var = $this->get_full($name); if(isset($var[$this->db_r_c_value])) { return $var[$this->db_r_c_value]; } else { return false; }	}	
		// Delete a Constant
		public function del($name) {
			if($var = $this->get_full($name)) {
				return @$this->variable_msqlcon->query("DELETE FROM ".$this->variable_table." WHERE ".$this->db_r_c_id." = ".$var[$this->db_r_c_id]." ;");	
			} else {return false;}}		

		// Internal Function to Handle Variables
		public function set($name, $value, $description = false, $add = true, $overwrite = true) {
			if(!$description OR !$this->db_r_c_descr) { $descriptionedit = false;$descriptioneditv = false; } else { $descriptionedit = " , ".$this->db_r_c_descr." = ? ";$descriptioneditv = $description;}
		
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$descriptioneditv;		
		
			if($this->exists($name)) {
				if($overwrite) {
					if($description) {
						if($this->db_r_c_section) {
							return @$this->variable_msqlcon->update("UPDATE ".$this->variable_table." SET ".$this->db_r_c_descr." = ?, ".$this->db_r_c_value." = '".$this->variable_msqlcon->escape(@$value)."' WHERE ".$this->db_r_c_title." = \"".$this->variable_msqlcon->escape(@$name)."\" AND (".$this->db_r_c_section." = '".$this->sections_name."');", $b); 								
						} else {
							return @$this->variable_msqlcon->update("UPDATE ".$this->variable_table." SET ".$this->db_r_c_descr." = ?, ".$this->db_r_c_value." = '".$this->variable_msqlcon->escape(@$value)."' WHERE ".$this->db_r_c_title." = \"".$this->variable_msqlcon->escape(@$name)."\";", $b); 		
						}							
					} else {
						if($this->db_r_c_section) {
							return @$this->variable_msqlcon->update("UPDATE ".$this->variable_table." SET ".$this->db_r_c_value." = '".$this->variable_msqlcon->escape(@$value)."' WHERE ".$this->db_r_c_title." = \"".$this->variable_msqlcon->escape(@$name)."\" AND (".$this->db_r_c_section." = '".$this->sections_name."');"); 								
						} else {
							return @$this->variable_msqlcon->update("UPDATE ".$this->variable_table." SET ".$this->db_r_c_value." = '".$this->variable_msqlcon->escape(@$value)."' WHERE ".$this->db_r_c_title." = \"".$this->variable_msqlcon->escape(@$name)."\";"); 		
						}					
					}
				}				
			} else {
				if($add) {
					if($description) {
						if($this->db_r_c_section) {
							return @$this->variable_msqlcon->query("INSERT INTO ".$this->variable_table."(".$this->db_r_c_title.", ".$this->db_r_c_value.", ".$this->db_r_c_descr.", ".$this->db_r_c_section.") VALUES('".$this->variable_msqlcon->escape(@$name)."', '".$this->variable_msqlcon->escape(@$value)."', ?, \"".$this->sections_name."\");", $b);							
						} else {
							return @$this->variable_msqlcon->query("INSERT INTO ".$this->variable_table."(".$this->db_r_c_title.", ".$this->db_r_c_value.", ".$this->db_r_c_section.", ".$this->db_r_c_descr.") VALUES('".$this->variable_msqlcon->escape(@$name)."', '".$this->variable_msqlcon->escape(@$value)."', \"".$this->sections_name."\", ?);", $b);
						}							
					} else {
						if($this->db_r_c_section) {
							return @$this->variable_msqlcon->query("INSERT INTO ".$this->variable_table."(".$this->db_r_c_title.", ".$this->db_r_c_value.", ".$this->db_r_c_section.") VALUES('".$this->variable_msqlcon->escape(@$name)."', '".$this->variable_msqlcon->escape(@$value)."', \"".$this->sections_name."\");");							
						} else {
							return @$this->variable_msqlcon->query("INSERT INTO ".$this->variable_table."(".$this->db_r_c_title.", ".$this->db_r_c_value.", ".$this->db_r_c_section.") VALUES('".$this->variable_msqlcon->escape(@$name)."', '".$this->variable_msqlcon->escape(@$value)."', \"".$this->sections_name."\");");
						}					
					}
				}
			} return false;
		}		

		// Get a Full Array from Row of Table with This name Found 1st Hit
		public function get_full($name) {
			if($this->db_r_c_section) {
				return @$this->variable_msqlcon->select("SELECT * FROM `".$this->variable_table."` WHERE (".$this->db_r_c_section." = '".$this->sections_name."' ) AND ".$this->db_r_c_title." = \"".$this->variable_msqlcon->escape(@$name)."\";");
			} else {
				return @$this->variable_msqlcon->select("SELECT * FROM `".$this->variable_table."` WHERE ".$this->db_r_c_title." = \"".$this->variable_msqlcon->escape(@$name)."\";");		
			}							
		}		
		
		// Setup Int
		public function form($varname, $type = "int", $selectarray = array()) {
			if(!isset($_SESSION["x_class_var"])) {$_SESSION["x_class_var"] = mt_rand(1000, 999999); }
			if(isset($_POST["x_class_var_submit_".$varname."_".$section])) {
				if($_POST["x_class_var_csrf"] == $_SESSION["x_class_var"]) {
						$finalvalue = false;
						switch($type) {
							case "int": $finalvalue = @$_POST["x_class_var_val"]; break;	
							case "text": $finalvalue = @$_POST["x_class_var_val"]; break;	
							case "string": $finalvalue = @$_POST["x_class_var_val"]; break;	
							case "select": $finalvalue = @$_POST["x_class_var_val"]; break;		
							case "bool": if(@$_POST["x_class_var_val"]) {$finalvalue =1;} else {$finalvalue =0;}  break;		
							case "array": $finalvalue = @serialize($_POST["x_class_var_val"]); break;					
						} 
					if($this->set($varname, $finalvalue, false, true, true)) {
						$text = "<b><font color='lime'>Changed successfully!</font></b>";
					} else {$text = "<b><font color='red'>Could not be changed!</font></b>";}
				} else { $text = "<b><font color='red'>CSRF Error Try Again!</font></b>"; }	
			} $current = $this->get_full($varname); ?>
			<div class="x_class_var">
				<form method="post" action="#x_class_var_ancor_<?php echo $varname.$section; ?>">
					<fieldset id="x_class_var_ancor_<?php echo $varname.$section; ?>">
						<?php if($description AND is_array($current)) { echo $current[$this->db_r_c_descr]; echo "<br />"; } ?>
						<?php if(isset($text)) { echo $text; echo "<br />"; } ?>
						<!-- Int -->
						<?php if($type == "int") { ?> <input type="number" value="<?php if(is_array($current)) { echo $current[$this->db_r_c_value]; } ?>" name="x_class_var_submit_val"><?php } ?>
						<!-- String -->
						<?php if($type == "string") { ?> <input type="text" value="<?php if(is_array($current)) { echo $current[$this->db_r_c_value]; } ?>" name="x_class_var_submit_val"><?php } ?>
						<!-- Text -->
						<?php if($type == "text") { ?> <textarea name="x_class_var_submit_val"><?php if(is_array($current)) { echo $current[$this->db_r_c_value]; } ?></textarea><br /><?php } ?>
						<!-- Bool -->
						<?php if($type == "bool" AND is_array($current) AND $current[$this->db_r_c_value] == 1) { $xxx = "checked"; } else { $xxx = ""; } ?>
						Activate: <input type="checkbox" name="x_class_var_submit_val" <?php echo $xxx; ?>>						
						<!-- Select -->
						<select name="x_class_var_submit_val">
						<?php $output = "";
							foreach($selectarray AS $key => $value) {
								if($current[$this->db_r_c_value] == $value[1]) { echo '<option value="'.$value[1].'">'.$value[0]."</option>"; }
								else { $output .= '<option value="'.$value[1].'">'.$value[0]."</option>"; }
							}
							echo $output;
						?>
						</select>
						<!-- Misc Form -->
						<input type="hidden" value="<?php echo $_SESSION["x_class_var"]; ?>" name="x_class_var_csrf">
						<input type="submit" value="Change" name="x_class_var_submit_<?php echo $varname.$section; ?>">
					</fieldset>
				</form>
			</div>
		<?php }	
	}
