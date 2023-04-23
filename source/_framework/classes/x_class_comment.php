<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/  Comment Control Class	*/
	
	class x_class_comment {
		// Class Variables
		private $mysqlobj   = false;
		private $table  	= false;
		private $pre    = false;
		private $target = false;
		private $module = false;
		
		// Default Message Variables
		private $sys_name	=	"System"; public function sys_name($name = "System") { $this->sys_name = $name; }
		private $sys_text	=	"Thanks for visiting my page and have a nice day!"; public function sys_text($text = "Thanks for visiting my page and have a nice day!") { $this->sys_text = $text; }
		
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
											  `status` tinyint(1) DEFAULT NULL COMMENT '0:Wait 1:OK 2: Sys 3:Start',
											  `upvotes` int(9) DEFAULT '0' COMMENT 'Upvote Counter for Starter Status',
											  `section` varchar(64) DEFAULT NULL COMMENT 'Related Section',
											  PRIMARY KEY (`id`)) ;");}
		
		// Construct Class
		function __construct($mysql, $table, $precookie, $module, $target) {
			if (session_status() === PHP_SESSION_NONE) { session_start(); }
			$this->mysqlobj = $mysql;
			$this->table    = $table;
			$this->pre      = $precookie;
			$this->target   = htmlspecialchars($target);
			$this->module   = htmlspecialchars($module);

			if(!isset($_SESSION[$this->pre."xc_comment"])) { $_SESSION[$this->pre."xc_comment"] == serialize(array()); }
			$tmp = @unserialize(@$_SESSION[$this->pre."xc_comment"]);
			if(!is_array($tmp)) { $tmp = array(); }
			
			$this->vote_arr = $tmp;
			foreach($this->vote_arr AS $key => $value) {
				if($value == $this->module.$this->target) { $this->vote_done = true; }
			}
			
			$val = false; try {
				$val = $this->mysqlobj->query( 'SELECT 1 FROM `'.$this->table.'`');
			} catch (Exception $e){ 
				 $this->create_table();
			} if($val === FALSE) { $this->create_table();}
		}
		
		// Show Vote Box
		function vote_show() { ?>
			<div class="x_comment_vote">
				<form method="get"><input type="hidden" name="x_comment_vote" value="execute">Currently <?php echo $this->upvote; ?> Upvotes!</font>
					<?php if(!$this->vote_done) { ?><input type="submit" value="Upvote!"><?php } else { ?><font class="x_comment_vote_done">You already have given an upvote for this item!</font><?php } ?></form> 
			</div> <?php 
		}			
		
		// Show Comments
		function comment_show($hide_system_msg = false) {
			echo '<div class="x_comment_comments">';
			$q	=	@$this->mysqlobj->query('SELECT * FROM '.$this->table .' WHERE (status = 1 OR status = 2 OR status = 3) AND target = "'.$this->module.'" AND targetid = "'.$this->target.'"  ORDER BY id DESC');
			while($r=@mysqli_fetch_array($q)){ if(!$hide_system_msg OR $r["status"] == 1) { echo '<div class="x_comment_comments_post"><div class="x_comment_comments_title">'.$r["name"].' - '.$r["creation"].'</div><div class="x_comment_comments_text">'.$r["text"].'</div></div>'; } }			
			echo "</div>";
		}		
		
		// Get Comments
		function comment_get($hide_system_msg = false) {
			$array = array();
			echo '<div class="x_comment_comments">';
			$q	=	@$this->mysqlobj->query('SELECT * FROM '.$this->table .' WHERE (status = 1 OR status = 2 OR status = 3) AND target = "'.$this->module.'" AND targetid = "'.$this->target.'"  ORDER BY id DESC');
			while($r=@mysqli_fetch_array($q)){ if(!$hide_system_msg OR $r["status"] == 1) { array_push($array, $r); } }			
			return $array;
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
		
		// Init Form
			// 1 - System Message Inserted
			// 2 - Vote OK
			// 3 - Comment Missing Fields
			// 4 - Comment Captcha Error
			// 5 - Comment OK
		function init($captcha_code_if_delivered = false) {
			// Insert System Entrie if Not Exists
			$q	= @$this->mysqlobj->query( 'SELECT * FROM '.$this->table .' WHERE status = 3 AND target = "'.$this->mysqlobj->escape($this->module).'" AND targetid = "'.$this->mysqlobj->escape($this->target).'"');
			if(mysqli_num_rows($q) <= 0) {
				@$this->mysqlobj->query( "INSERT INTO ".$this->table ." (target, targetid, name, text, status) VALUE('".$this->mysqlobj->escape($this->module)."','".$this->mysqlobj->escape($this->target)."','".$this->mysqlobj->escape($this->sys_name)."', '".$this->mysqlobj->escape($this->sys_text)."', 3);"); 
				$this->init_res = 1;
			}

			// Endorse Counter Update
			$q	= @$this->mysqlobj->query( 'SELECT * FROM '.$this->table .' WHERE status = 3 AND target = "'.$this->mysqlobj->escape($this->module).'" AND targetid = "'.$this->mysqlobj->escape($this->target).'"');
			if(mysqli_num_rows($q) > 0) {
				if($r 	= @mysqli_fetch_array($q) ) {
					$this->upvote = $r["upvotes"];
				} else { $this->upvote = 0; }					
			} else { $this->upvote = 0; } 

			// Comment Counter Update
			$q	= @$this->mysqlobj->query( 'SELECT * FROM '.$this->table .' WHERE target = "'.$this->mysqlobj->escape($this->module).'" AND targetid = "'.$this->mysqlobj->escape($this->target).'"');
			if(mysqli_num_rows($q) > 0) {
				$this->comment = mysqli_num_rows($q);
			} else { $this->comment = 0; } 
			
			// New Vote
			if(!$this->vote_done) {
				if(@$_GET["x_comment_vote"]  == "execute") {
					array_push($_SESSION[$this->pre."xc_comment"], $this->module.$this->target);
					array_push($this->vote_arr, $this->module.$this->target);
					$this->vote_done = true;
					$this->init_res = 2;
					@$this->mysqlobj->update( "UPDATE ".$this->table ." SET upvotes = upvotes + 1 WHERE target = '".$this->mysqlobj->escape($this->module)."' AND targetid = '".$this->mysqlobj->escape($this->target)."' AND status = 3");	
				}
			}
				
			// New Comment
			if(isset($_POST["x_comment_submit"])) {
				if (trim(@$_POST["x_comment_name"]) != "" AND trim(@$_POST["x_comment_text"]) != "" AND isset($_POST["x_comment_text"]) AND isset($_POST["x_comment_name"])){
					if (trim(strtolower(@$_POST["x_comment_name"])) == $this->sys_name){$_POST["x_comment_name"] = "Guest_".trim(strtolower(@$_POST["x_comment_name"])); }
					if (@$captcha_code_if_delivered == @$_POST["x_comment_captcha"]){
						$bind[0]["value"] = $_POST["x_comment_name"];
						$bind[0]["type"] = "s";
						$bind[1]["value"] = $_POST["x_comment_text"];
						$bind[1]["type"] = "s";
						$comment_sql1	=	'INSERT INTO '.$this->table .'(name, creation, text, target, targetid, status)VALUES(?, "'.date("Y-m-d H:i:s").'", ?, "'.$this->mysqlobj->escape($this->module).'", "'.$this->mysqlobj->escape($this->target).'", 0)';
						$comment_r1	=	$this->mysqlobj->query( $comment_sql1, $bind);
						$this->init_res = 5;
					} else { $this->init_res = 4; }
				} else { $this->init_res = 3; }
			} 
		}	
	} 
