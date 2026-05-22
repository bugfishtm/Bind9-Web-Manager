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
	
	class x_class_comment {
		// Class Variables
		private $mysqlobj   = false;
		private $table  	= false;
		
		// Target for a Post
		private $pre    = false;
		private $target = false;
		private $module = false;
		private $section = false;
		
		// Default Message Variables
		private $sys_name	=	"System"; 
			public function sys_name($name = "System") { $this->sys_name = $name; }
		private $sys_text	=	"Commenting System Initialized! Have a very nice day!"; 
			public function sys_text($text = "Commenting System Initialized! Have a very nice day!") { $this->sys_text = $text; }
		
		// Comment Informations
		public $upvote = false;
		public $comment = false;
		public $init_res = false;
		
		// Internal Informations
		private $vote_done = false;
		private $vote_arr = array();
		
		// Create Table Init			
		private function create_table() {
			$this->mysqlobj->query("CREATE TABLE IF NOT EXISTS `".$this->table."` (
									  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
									  `target` varchar(256) DEFAULT NULL COMMENT 'Target Name',
									  `targetid` varchar(256) DEFAULT NULL COMMENT 'Target ID',
									  `name` varchar(256) NOT NULL COMMENT 'Autor Name',
									  `text` text  NOT NULL COMMENT 'Activitie Text',
									  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date | Auto Set',
									  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date | Auto - Set',
									  `status` tinyint(1) DEFAULT NULL COMMENT '0:Wait 1:OK 2:Internal 3:System',
									  `upvotes` int(9) DEFAULT '0' COMMENT 'Upvote Counter for Starter Status',
									  `section` varchar(128) DEFAULT NULL COMMENT 'Related Section',
									  PRIMARY KEY (`id`)) ;");}
		
		// Construct Class
		function __construct($mysql, $table, $precookie, $module, $target, $section = "") {
			if (session_status() === PHP_SESSION_NONE) { session_start(); }
			$this->mysqlobj = $mysql;
			$this->table    = $table;
			$this->pre      = $precookie;
			$this->target   = $target;
			$this->section   = $section;
			$this->module   = $module;
			
			// Prepare Commented Array
			if(!@unserialize(@$_SESSION[$this->pre."x_class_comment"])) { $_SESSION[$this->pre."x_class_comment"] = serialize(array());}
			if(!@isset($_SESSION[$this->pre."x_class_comment"])) { $_SESSION[$this->pre."x_class_comment"] == serialize(array()); }
			$tmp = @unserialize(@$_SESSION[$this->pre."x_class_comment"]);
			if(!is_array($tmp)) { $this->vote_arr = array(); } else { $this->vote_arr = $tmp; }
			
			// CHeck if Vote is Done For Current Object
			foreach($this->vote_arr AS $key => $value) {
				if($value == $this->module.$this->target) { $this->vote_done = true; }
			}			
			
			// Create Table if not Exists
			if(!$this->mysqlobj->table_exists($table)) { $this->create_table(); $this->mysqlobj->free_all();  }
		}
		
		// Show Vote Box
		function vote_show() { ?>
			<div class="x_comment_vote">
				<form method="get"><input type="hidden" name="x_comment_vote" value="vote">Currently <?php echo $this->upvote; ?> Upvotes!</font>
					<?php if(!$this->vote_done) { ?><input type="submit" value="Upvote!"><?php } else { ?><font class="x_comment_vote_done">You already have given an upvote for this item!</font><?php } ?></form> 
			</div> <?php 
		}			
		
		// Comment Form
		function form_show($captchaurl) { ?>			
			<div class="x_comment_form">	
				<form method="post" class="x_comment_activeform">
					<?php
						echo '<input type="text" name="x_comment_name" placeholder="Name" maxlength="64"/>';
						echo '<textarea name="x_comment_text" placeholder="Comment" maxlength="256"></textarea><br />';
						echo '<img src="'.$captchaurl.'" alt="captcha image">' ;
						echo '<input type="text" name="x_comment_captcha" id="x_comment_captcha" placeholder="Captcha" size="10" maxlength="64"/>';
						echo '<input type="submit" name="x_comment_submit" value="Add" />';
					?>
				</form>					
			</div>				
		<?php }			
		
		// Comment Status
			// 0:Wait
			// 1:OK 
			// 2:Internal
			// 3:System		
		
		// Show Comments
		function comment_show($hide_system_msg = false, $hide_internal_msg = false, $hide_confirmed = false, $hide_unconfirmed = false, $sorting = " ORDER BY id DESC") {
			echo '<div class="x_comment_comments">';
			$array = array();
			$bind[0]["value"] = $this->module;
			$bind[0]["type"] =  "s";
			$bind[1]["value"] = $this->target;
			$bind[1]["type"] =  "s";
			$bind[2]["value"] = $this->section;
			$bind[2]["type"] =  "s";
			$q	=	@$this->mysqlobj->query('SELECT * FROM `'.$this->table .'` WHERE target = ? AND targetid = ?  AND section = ? '.$sorting, $bind);
			while($r=@mysqli_fetch_array($q)){ 
				 if($hide_system_msg AND $r["status"] == 3) { continue; }
				 if($hide_internal_msg AND $r["status"] == 2) { continue; }
				 if($hide_confirmed AND $r["status"] == 1) { continue; }
				 if($hide_unconfirmed AND $r["status"] == 0) { continue; }
				 array_push($array, $r);
				echo '<div class="x_comment_comments_post"><div class="x_comment_comments_title">'.$r["name"].' - '.$r["creation"].'</div><div class="x_comment_comments_text">'.$r["text"].'</div></div>';
			}
			echo "</div>";
			return $array;
		}		
		
		// Get Comments
		function comment_get($hide_system_msg = false, $hide_internal_msg = false, $hide_confirmed = false, $hide_unconfirmed = false, $sorting = " ORDER BY id DESC") {
			$array = array();
			$bind[0]["value"] = $this->module;
			$bind[0]["type"] =  "s";
			$bind[1]["value"] = $this->target;
			$bind[1]["type"] =  "s";
			$bind[2]["value"] = $this->section;
			$bind[2]["type"] =  "s";
			$q	=	@$this->mysqlobj->query('SELECT * FROM `'.$this->table .'` WHERE target = ? AND targetid = ? AND section = ? '.$sorting, $bind);
			while($r=@mysqli_fetch_array($q)){ 
				 if($hide_system_msg AND $r["status"] == 3) { continue; }
				 if($hide_internal_msg AND $r["status"] == 2) { continue; }
				 if($hide_confirmed AND $r["status"] == 1) { continue; }
				 if($hide_unconfirmed AND $r["status"] == 0) { continue; }
				 array_push($array, $r);
			}
			return $array;
		}		

		// Init Commenting System and Returning Messages
			// Init Form
			// 1 - System Message Inserted
			// 2 - Vote OK
			// 3 - Comment Missing Fields
			// 4 - Comment Captcha Error
			// 5 - Comment OK	
		function init($captcha_code_if_delivered = false) {
			$bind[0]["value"] = $this->module;
			$bind[0]["type"] =  "s";
			$bind[1]["value"] = $this->target;
			$bind[1]["type"] =  "s";
			$bind[2]["value"] = $this->section;
			$bind[2]["type"] =  "s";

			// Insert System Entrie if Not Exists
			$q	= @$this->mysqlobj->query( 'SELECT * FROM `'.$this->table .'` WHERE status = 3 AND target = ? AND targetid = ? AND section = ? ', $bind);
			if(mysqli_num_rows($q) <= 0) {
				$bind1[0]["value"] = $this->module;
				$bind1[0]["type"] =  "s";
				$bind1[1]["value"] = $this->target;
				$bind1[1]["type"] =  "s";			
				$bind1[2]["value"] = $this->sys_name;
				$bind1[2]["type"] =  "s";
				$bind1[3]["value"] = $this->sys_text;
				$bind1[3]["type"] =  "s";		
				$bind1[4]["value"] = $this->section;
				$bind1[4]["type"] =  "s";
				@$this->mysqlobj->query( "INSERT INTO `".$this->table ."` (target, targetid, name, text, status, section) VALUE(?,?,?,?, 3, ?);", $bind1); 
				$this->init_res = 1;
			}
			
			// Endorse Counter Update
			$q	= @$this->mysqlobj->query( 'SELECT * FROM `'.$this->table .'` WHERE status = 3 AND target = ? AND targetid = ? AND section = ? ', $bind);
			if(mysqli_num_rows($q) <= 0) {
				if($r 	= @mysqli_fetch_array($q) ) {
					$this->upvote = $r["upvotes"];
				} else { $this->upvote = 0; }
			} else { $this->upvote = 0; } 

			// Comment Counter Update
			$q	= @$this->mysqlobj->query( 'SELECT * FROM `'.$this->table .'` WHERE target = ? AND targetid = ? AND section = ? ', $bind);
			if(mysqli_num_rows($q) <= 0) {	
				$this->comment = mysqli_num_rows($q);
			} else { $this->comment = 0; } 
			
			
			// New Vote
			if(!$this->vote_done) {
				if(@$_GET["x_comment_vote"]  == "vote") {
					array_push($_SESSION[$this->pre."x_class_comment"], $this->module.$this->target);
					array_push($this->vote_arr, $this->module.$this->target);
					$this->vote_done = true;
					$this->init_res = 2;
					@$this->mysqlobj->update( "UPDATE `".$this->table ."` SET upvotes = upvotes + 1 WHERE target = ? AND targetid = ? AND status = 3  AND section = ? ", $bind);
				}
			}
			// New Comment
			if(isset($_POST["x_comment_submit"])) {
				if (trim(@$_POST["x_comment_name"] ?? '') != "" AND trim(@$_POST["x_comment_text"] ?? '') != "" AND isset($_POST["x_comment_text"]) AND isset($_POST["x_comment_name"])){
					if (trim(strtolower(@$_POST["x_comment_name"] ?? '') ?? '') == $this->sys_name){$_POST["x_comment_name"] = "Guest_".trim(strtolower(@$_POST["x_comment_name"] ?? '') ?? ''); }
					if (@$captcha_code_if_delivered == @$_POST["x_comment_captcha"] AND @$captcha_code_if_delivered != false){
						$bind[0]["value"] = $_POST["x_comment_name"];
						$bind[0]["type"] = "s";
						$bind[1]["value"] = $_POST["x_comment_text"];
						$bind[1]["type"] = "s";						
						$bind[2]["value"] = $this->module;
						$bind[2]["type"] = "s";
						$bind[3]["value"] = $this->target;
						$bind[3]["type"] = "s";					
						$bind[4]["value"] = $this->section;
						$bind[4]["type"] =  "s";				
						$comment_sql1	=	'INSERT INTO `'.$this->table .'`(name, text, target, targetid, status, section)VALUES(?, ?, ?, ?, 0, ?)';
						$comment_r1	=	$this->mysqlobj->query( $comment_sql1, $bind);
						$this->init_res = 5;
					} else { $this->init_res = 4; }
				} else { $this->init_res = 3; }
			} 
		}
	} 
