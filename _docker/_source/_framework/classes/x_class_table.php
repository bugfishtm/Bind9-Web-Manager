<?php                                                  
	#	@@@@@@@  @@@  @@@  @@@@@@@  @@@@@@@@ @@@  @@@@@@ @@@  @@@ 
	#	@@!  @@@ @@!  @@@ !@@       @@!      @@! !@@     @@!  @@@ 
	#	@!@!@!@  @!@  !@! !@! @!@!@ @!!!:!   !!@  !@@!!  @!@!@!@! 
	#	!!:  !!! !!:  !!! :!!   !!: !!:      !!:     !:! !!:  !!! 
	#	:: : ::   :.:: :   :: :: :   :       :   ::.: :   :   : : 						
	#		 ______  ______   ______   _________   ______  _   _   _   ______   ______   _    __ 
	#		| |     | |  | \ | |  | | | | | | | \ | |     | | | | | | / |  | \ | |  | \ | |  / / 
	#		| |---- | |__| | | |__| | | | | | | | | |---- | | | | | | | |  | | | |__| | | |-< <  
	#		|_|     |_|  \_\ |_|  |_| |_| |_| |_| |_|____ |_|_|_|_|_/ \_|__|_/ |_|  \_\ |_|  \_\ 
																							 
	#	Copyright (C) 2025 Jan Maurice Dahlmanns [Bugfish]

	#	This program is free software; you can redistribute it and/or
	#	modify it under the terms of the GNU Lesser General Public License
	#	as published by the Free Software Foundation; either version 2.1
	#	of the License, or (at your option) any later version.

	#	This program is distributed in the hope that it will be useful,
	#	but WITHOUT ANY WARRANTY; without even the implied warranty of
	#	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	#	GNU Lesser General Public License for more details.

	#	You should have received a copy of the GNU Lesser General Public License
	#	along with this program; if not, see <https://www.gnu.org/licenses/>.
	
	class x_class_table {
		// Class Variables
		private $mysql     	= false;
		private $table     	= false;
		private $id     	= false;
		private $idf    	= false;
		private $csrf    	= false;
		public $csrfobj    = false;
		
		// Constructor
		function __construct($mysql, $table_name, $id = false, $id_field = "id") {
			$this->mysql 	= $mysql;
			$this->table 	= $table_name;
			$this->idf 		= $id_field;
			if(!$id) { $this->id 		= ""; } else { $this->id 		= $id; }
			// Check last CSRF or Renew
			$this->csrfobj = new x_class_csrf("x_class_table".$this->id);
			if($this->csrfobj->check(@$_POST["x_class_table_exec_csrf".$this->id])) { $this->csrf = true; }}	

		// Spawn Deleting Exec
		public function exec_delete($ovr_csrf = false) {
			if(isset($_POST["x_class_table_exec_del_submit".$this->id])) {  
				if(@is_numeric(@$_POST["x_class_table_exec_delete".$this->id])) { 
					if($this->csrf OR $ovr_csrf) { 
						$this->mysql->query("DELETE FROM `".$this->table."` WHERE `".$this->idf."` = '".$_POST["x_class_table_exec_delete".$this->id]."'");
						return "deleted";
					} else { return "csrf"; }
				}
			}
		}  

		// Config For Creation and Editing
		private $rel_url     		= ""; 
		
		// Setup Arrays
		public function config_rel_url($rel_url) {
			$this->rel_url		= $rel_url;
		}
								
		// Config For Creation and Editing
		private $create_array     	= false; 
		private $edit_array     	= false;
		
		// Setup Arrays
		public function config_array($create, $edit) {
			$this->create_array		= $create;
			$this->edit_array		= $edit;
		}
		
		// Spawn Edit Exec
		public function exec_edit() {
			if(@is_array($this->edit_array)) {
				if(@isset($_POST["x_class_table_exec_edit_submit".$this->id])) {   
					if(@is_numeric(@$_POST["x_class_table_exec_edit".$this->id])) {  
						if($this->csrf) { 
							foreach($this->edit_array as $key => $value) {
								
								
								if(!isset($_POST["x_class_table_post_".$this->id."_".$value["field_name"]]) AND $value["field_type"] != "int") {
									if(!@$value["field_default"] AND !is_numeric(@$value["field_default"])) { $value_now = ""; }
									else {  $value_now = $value["field_default"]; }
								} elseif(!is_numeric($_POST["x_class_table_post_".$this->id."_".$value["field_name"]]) AND $value["field_type"] == "int") {
									if(!@$value["field_default"]) { $value_now = 0; }
									else {  $value_now = $value["field_default"]; }
									
									if(is_numeric($value["field_int_min"])) { 
										if($value["field_int_min"] > $value["field_default"]) { 
											$value_now = $value["field_int_min"];
										}
									}
								} else {$value_now = @$_POST["x_class_table_post_".$this->id."_".$value["field_name"]]; }
								
								
								$b[0]["value"] = $value_now;
								$b[0]["type"] = "s";
								$this->mysql->query("UPDATE `".$this->table."` SET `".$value["field_name"]."` = ? WHERE `".$this->idf."` = '".$_POST["x_class_table_exec_edit".$this->id]."'", $b);
							}	return "edited";
						} else { return "csrf"; }
					}
				}
			}
		}		
		
		// Spawn Create Exec
		public function exec_create() {  
			if(@is_array($this->create_array)) { 
				if(@isset($_POST["x_class_table_exec_create_submit".$this->id])) {  
					if($this->csrf) {
						$b = array();
						$bt = "";
						$bs = "";
						foreach($this->create_array as $key => $value) {
							
							
								
								if(!isset($_POST["x_class_table_post_".$this->id."_".$value["field_name"]]) AND $value["field_type"] != "int") {
									if(!@$value["field_default"] AND !is_numeric(@$value["field_default"])) { $value_now = ""; }
									else {  $value_now = $value["field_default"]; }
								} elseif(!is_numeric($_POST["x_class_table_post_".$this->id."_".$value["field_name"]]) AND $value["field_type"] == "int") {
									if(!@$value["field_default"]) { $value_now = 0; }
									else {  $value_now = $value["field_default"]; }
									
									if(is_numeric($value["field_int_min"])) { 
										if($value["field_int_min"] > $value["field_default"]) { 
											$value_now = $value["field_int_min"];
										}
									}
								} else {$value_now = @$_POST["x_class_table_post_".$this->id."_".$value["field_name"]]; }
							
							
							$b[$key]["value"] = $value_now;
							$b[$key]["type"] = "s";		
							if($key != 0) { $bs .=	", ? ";	} else { $bs .=	" ? "; } 				
							if($key != 0) { $bt .=	", `".$value["field_name"]."` ";	} else { $bt .=	" `".$value["field_name"]."` "; } 	
						}	
						$this->mysql->query("INSERT INTO `".$this->table."`(".$bt.") VALUES(".$bs.");", $b);
						unset($b);
						unset($bt);
						unset($bs);
						return "created";
					} else { return "csrf"; }
				}
			}
		}		
		
		// Spawn Return Message Box
		public function spawn_return($deleted = "The item has been deleted!", $csrf = "CSRF Code expired, please try again!", $edited = "The item has been edited!", $created = "The item has been created!") {
			if(@$_POST["x_class_table_return_type".$this->id] == "deleted") {
				echo "<div class='x_class_table_box_return x_class_table_box_return_ok' id='x_class_table_return_id_".$this->id."'>";
					echo $deleted;
				echo "</div>";
			} elseif(@$_POST["x_class_table_return_type".$this->id] == "created") {
				echo "<div class='x_class_table_box_return x_class_table_box_return_ok' id='x_class_table_return_id_".$this->id."'>";
					echo $created;
				echo "</div>";
			} elseif(@$_POST["x_class_table_return_type".$this->id] == "edited") {
				echo "<div class='x_class_table_box_return x_class_table_box_return_ok' id='x_class_table_return_id_".$this->id."'>";
					echo $edited;
				echo "</div>";
			} elseif(@$_POST["x_class_table_return_type".$this->id] == "csrf") {
				echo "<div class='x_class_table_box_return x_class_table_box_return_error' id='x_class_table_return_id_".$this->id."'>";
					echo $csrf;
				echo "</div>";
			}
		}	

		// Spawn Creating Area
		public function spawn_create($button_name = "Create Item", $button_class = "", $add_info = array()) {
			if(@is_array($this->create_array)) {
				echo "<div class='x_class_table_box_create' id='x_class_table_create_id_".$this->id."'>";
					echo "<form method='post' action='".$this->rel_url."'><input type='hidden' name='x_class_table_exec_csrf".$this->id."' value='".$this->csrfobj->get()."'>"; 
						foreach($this->create_array as $key => $value) { if(isset($value["field_title"])) { echo "<b>".$value["field_title"]."</b><br />"; } if(isset($value["field_descr"])) { echo $value["field_descr"]."<br />"; }?>
								<?php 
									if(@$value["field_label"]) {
										echo '<span class="x_class_table_label">';
										echo $value["field_label"]; 
										echo '</span>'; 
									}
								?>
							<!-- Int -->
							<?php if($value["field_type"] == "int") { ?> <input class="<?php echo @$value["field_classes"]; ?>" placeholder="<?php echo @$value["field_ph"]; ?>"  type="number" value="<?php echo $value["field_pre"]; ?>" name="x_class_table_post_<?php echo $this->id."_".$value["field_name"]; ?>"><br /><?php } ?>				
							<!-- String -->
							<?php if($value["field_type"] == "string") { ?> <input class="<?php echo @$value["field_classes"]; ?>" placeholder="<?php echo @$value["field_ph"]; ?>"  type="text" value="<?php echo @$value["field_pre"]; ?>" name="x_class_table_post_<?php echo $this->id."_".$value["field_name"]; ?>"><br /><?php } ?>					
							<!-- Text -->
							<?php if($value["field_type"] == "text") { ?> <textarea class="<?php echo @$value["field_classes"]; ?>" placeholder="<?php echo @$value["field_ph"]; ?>"  name="x_class_table_post_<?php echo $this->id."_".$value["field_name"]; ?>"><?php echo @$value["field_pre"]; ?></textarea><br /><?php } ?>
							<!-- Bool -->
							<?php if(false) { ?>Configure: <input class="<?php echo @$value["field_classes"]; ?>" type="checkbox" name="x_class_table_post_<?php echo $this->id."_".$value["field_name"]; ?>" ><br /><?php } ?>		
							<!-- Select -->
							<?php if($value["field_type"] == "select") { ?>
								<select class="<?php echo $value["field_classes"]; ?>"  name="x_class_table_post_<?php echo $this->id."_".$value["field_name"]; ?>">
									<?php
										 foreach($value["select_array"] AS $key => $valuex) { if(is_array($valuex)) {
												if($valuex[1] == @$value["field_pre"]) { $seltmp = "selected"; } else { $seltmp = ""; }
											echo '<option value="'.$valuex[1].'" '.$seltmp.'>'.$valuex[0]."</option>";
										} else {
												if($valuex == @$value["field_pre"]) { $seltmp = "selected"; } else { $seltmp = ""; }
												echo '<option value="'.$valuex.'" '.$seltmp.'>'.$valuex."</option>";	
										} }
									?>
								</select><br />
							<?php } ?>								
						<?php }	
					echo "<input type='submit' class='".$button_class."' value='".$button_name."' name='x_class_table_exec_create_submit".$this->id."'>";
					echo "<input type='hidden' value='1' name='x_class_table_exec_create_submit".$this->id."'>";
					echo "</form>";
				echo "</div>";
			}
		}
		
		// Spawn Editing Area
		public function spawn_edit($button_name = "Edit Item", $button_class = "", $add_info = array()) {
			if(@is_array($this->edit_array) AND is_numeric(@$_POST["x_class_table_exec_edit".$this->id])) {
				echo "<div class='x_class_table_box_edit' id='x_class_table_edit_id_".$this->id."'>";
					echo "<form method='post' action='".$this->rel_url."'><input type='hidden' name='x_class_table_exec_csrf".$this->id."' value='".$this->csrfobj->get()."'>"; 
						//if(is_numeric(@$_GET["x_class_table_edit".$this->id])) {
							$current = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE `".$this->idf."` = '".$_POST["x_class_table_exec_edit".$this->id]."'", false);
							foreach($this->edit_array as $key => $value) { if(isset($value["field_title"])) { echo "<b>".$value["field_title"]."</b><br />"; } if(isset($value["field_descr"])) { echo $value["field_descr"]."<br />"; }?>
								<?php 
									if($value["field_label"]) {
										echo '<span class="x_class_table_label">';
										echo $value["field_label"]; 
										echo '</span>'; 
									}
								?>
								
								<!-- Int -->
								<?php if($value["field_type"] == "int") { ?> <input class="<?php echo $value["field_classes"]; ?>" placeholder="<?php echo $value["field_ph"]; ?>"  type="number" value="<?php echo htmlentities(@$current[$value["field_name"]] ?? ''); ?>" name="x_class_table_post_<?php echo $this->id."_".$value["field_name"]; ?>"><br /><?php } ?>				
								<!-- String -->
								<?php if($value["field_type"] == "string") { ?> <input class="<?php echo $value["field_classes"]; ?>" placeholder="<?php echo $value["field_ph"]; ?>"  type="text" value="<?php echo htmlentities(@$current[$value["field_name"]] ?? ''); ?>" name="x_class_table_post_<?php echo $this->id."_".$value["field_name"]; ?>"><br /><?php } ?>					
								<!-- Text -->
								<?php if($value["field_type"] == "text") { ?> <textarea class="<?php echo $value["field_classes"]; ?>" placeholder="<?php echo $value["field_ph"]; ?>"  name="x_class_table_post_<?php echo $this->id."_".$value["field_name"]; ?>"><?php echo nl2br(htmlspecialchars($current[$value["field_name"]] ?? '')); ?></textarea><br /><?php } ?>
								<!-- Bool -->
								<?php if(false) { ?>Configure: <input class="<?php echo $value["field_classes"]; ?>" type="checkbox" placeholder="<?php echo $value["field_ph"]; ?>" name="x_class_table_post_<?php echo $this->id."_".$value["field_name"]; ?>" ><br /><?php } ?>		
								
								
								<!-- Select -->
								<?php if($value["field_type"] == "select") { ?>
									<select class="<?php echo $value["field_classes"]; ?>"  name="x_class_table_post_<?php echo $this->id."_".$value["field_name"]; ?>">
										<option value="<?php echo htmlentities($current[$value["field_name"]] ?? ''); ?>"><?php 
											$nochange = @htmlentities($current[$value["field_name"]] ?? '');
											if(is_array($value["select_array"])) {
												foreach($value["select_array"] AS $kk => $vv) {
													if(is_array($vv)) {
														if($vv[1] == $current[$value["field_name"]]) {
															$nochange = @htmlentities($vv[0] ?? '');
														}
													}
												}
											}
											echo $nochange; echo "</option>";
											 foreach($value["select_array"] AS $key => $value) { if(is_array($value)) {
												echo '<option value="'.$value[1].'">'.$value[0]."</option>";
											} else {
													echo '<option value="'.$value.'">'.$value."</option>";	
											} }
										?>
									</select><br />
								<?php } ?>								
							<?php }	
						//}
					echo "<input type='submit' class='".$button_class."' value='".$button_name."' name='x_class_table_exec_edit_submit".$this->id."'>";
					echo "<input type='hidden' value='".@$_POST["x_class_table_exec_edit".$this->id]."' name='x_class_table_exec_edit".$this->id."'>";
					echo "</form>";
				echo "</div>";
			}
		}
		
		// Spawn table Area
		public function spawn_table($title_array, $value_array, $editing = false, $deleting = false, $creating = false, $action_column = "Action", $classes = "", $add_info = array()) {
			echo "<div class='x_class_table_box_table' id='x_class_table_id_".$this->id."'>";
				if($creating) { echo "<a href='".$this->rel_url."'>".$creating."</a><br /><br />";}
				echo '<table id=\'x_class_table_id_tbl_'.$this->id.'\' class="x_class_table_listing '.$classes.'">';
					echo '<thead>';
						echo '<tr>';
							if(is_array($title_array)) {
								foreach($title_array AS $key => $value) {
									echo "<td>".$value["name"]."</td>";
								}
								if($deleting OR $editing) {
									echo "<td>".$action_column."</td>";
								}
							}			
						echo '</tr>';
					echo '</thead>';
					echo '<tbody>';
						if(is_array($value_array)) {
							foreach($value_array AS $key => $value) {
								echo "<tr>";
								if($editing) { $id = $value[$this->idf]; }
								if($deleting) { $id = $value[$this->idf]; }
								foreach($value AS $keyx => $valuex) {  if($keyx == $this->idf) { continue;} 
									echo "<td>";
										echo $valuex;
									echo "</td>";
								}
								if($deleting OR $editing) {
									echo "<td>";
										if($editing) { echo "<form method='post' class='x_class_table_button x_class_table_button_delete' action='".$this->rel_url."'><input type='hidden' name='x_class_table_exec_edit".$this->id."' value='".$id."' ><button type='submit' name='x_class_table_exec_ed_submit".$this->id."'>".$editing."</button></form>"; }
										if($deleting) { echo "<form method='post' action='".$this->rel_url."' class='x_class_table_button x_class_table_button_delete'><input type='hidden' name='x_class_table_exec_delete".$this->id."' value='".$id."'><input type='hidden' name='x_class_table_exec_del_submit".$this->id."' value='".$id."'><input type='hidden' name='x_class_table_exec_csrf".$this->id."' value='".$this->csrfobj->get()."'><button type='submit' name='x_class_table_exec_del_submit".$id."'>".$deleting."</button></form>"; }
									echo "</td>"; 
									
								}								
								echo "</tr>";	
							}
						}			
					echo '</tbody>';
				echo '</table>';			
			echo "</div>";}
	}