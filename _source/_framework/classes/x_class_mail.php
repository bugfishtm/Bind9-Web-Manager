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
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

	class x_class_mail {
		// Variables for Mail Setup
		private $host     		= false;	 // The host example : server.domain
		private $smtpauth 		= false;	 // Needs Auth?
		private $username 		= "";        // The Username for Auth
		private $password		= "";        // The Password for Auth
		private $port    		= 25;        // The Port of Server example: 25	
		private $smtpsecure 	= false;	 // Is Secure Connection
		// More Detailed Mail Settings
		private $keep_alive   	= false; 	public function keep_alive($bool = false) { $this->keep_alive = $bool; }
		private $encoding 		= 'base64'; public function encoding($encode = 'base64') {$this->encoding = $encode;}
		private $charset 		= "UTF-8";  public function charset($charset = "UTF-8") {$this->charset = $charset;} 
		private $allow_insecure_connection = false; public function allow_insecure_ssl_connections($bool = false) {$this->allow_insecure_connection = $bool;}
		private $smtpdebuglevel 	= 0;	 	public function smtpdebuglevel($int = 0) {$this->smtpdebuglevel = $int;} # 0  - lowest | 3 - highest
		// Settings Class Related
		private $html 			= false; public function all_default_html($bool = false) {$this->html = $bool;}
		private $header 		= ""; 	 private $footer = ""; public function change_default_template($header, $footer) { $this->header = $header; $this->footer = $footer; }
		private $setFromName 	= false; 	private $setFromMail 	= false; public function initFrom($mail, $name = false) {$this->setFromMail = $mail;$this->setFromName = $name;}
		private $addReplyToName = false; 	private $addReplyToMail = false; public function initReplyTo($mail, $name = false) {$this->addReplyToMail = $mail;$this->addReplyToName = $name;}		
		private $test_mode   	= false; 	public function test_mode($val) { $this->test_mode = $val; } 
		// Class Variables Private for Logging
		private $l_active  		= false; // Logging Enabled?
		private $l_mysql  		= false; // MySQL for Logging
		private $l_table		= false; // Table for Logging
		private $l_section   	= ""; // Section for Logging
		private $l_ok   		= false; // Log Successfull Sebd Mails
		// Misc Variables
		private $last_info   	= false; public function last_info() { return $this->last_info; } private function set_info($info) { $this->last_info = $info; } 
		// Log Functions
		public function log_disable() { $this->l_active = false; }
		public function log_enable() { $this->l_active = true; }
		// Config Logging and Enable
		public function logging($connection, $table, $log_success_mail = false, $section = "") { 
			$this->l_active = true;
			$this->l_mysql = $connection; 
			$this->l_table = $table; 
			$this->l_ok = $log_success_mail; 
			$this->l_section = $section; 
			if(!$this->l_mysql->table_exists($table)) { $this->create_table(); $this->l_mysql->free_all();  }
		}		

		// Table Init
		private function create_table() {
			$this->l_mysql->query("CREATE TABLE IF NOT EXISTS `".$this->l_table."` (
											  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Identificator',
											  `receiver` text DEFAULT NULL COMMENT 'Mail Receiver Serialized',
											  `bcc` text DEFAULT NULL COMMENT 'Mail BCC Serialized',
											  `cc` text DEFAULT NULL COMMENT 'Mail CC Serialized',
											  `attach` text DEFAULT NULL COMMENT 'Mail Attachments Serialized',
											  `subject` varchar(512) DEFAULT NULL COMMENT 'Mail Subject',
											  `msgtext` text COMMENT 'Mail Text',
											  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date | Auto - Set',
											  `success` tinyint(1) DEFAULT NULL COMMENT '1 - Mail OK Sended | Else - Mail Error',
											  `debugmsg` text COMMENT 'Debug Message',
											  `section` varchar(64) DEFAULT NULL COMMENT 'Related Section',
											  PRIMARY KEY (`id`));");}		
		
		// Construct
		function __construct($host, $port = 25, $auth_type = false, $user = false, $pass = false, $from_mail = false, $from_name = false) {
			$this->host 		 = $host; // The host example : server.domain
			if($auth_type == "ssl" OR $auth_type == "tls") { $this->smtpauth 	 = true; } else { $this->smtpauth 	 = false; } 
			$this->username 	 = $user; // The Username for Auth
			$this->password 	 = $pass; // The Password fot Auth
			$this->smtpsecure 	 = $auth_type; // "tls" or "ssl"
			$this->port 		 = $port;  // The Port of Server example: 25
			$this->setFromMail = $from_mail; 
			$this->setFromName  = $from_name; } 
		
		// Execute Mail Logging if Needed
		private function log_execute($subject, $content, $receiver, $attachments, $cc, $bcc, $success, $debug_message, $settings) {		
			if($this->l_active) { if($success) { $success = 1; }
			else { $success = 0; }
			if($success AND $this->l_ok) { return false; }
			$b[0]["type"] = "s";
			$b[0]["value"] = @serialize(@$receiver);
			$b[1]["type"] = "s";
			$b[1]["value"] = @serialize(@$bcc);
			$b[2]["type"] = "s";
			$b[2]["value"] = @serialize(@$cc);
			$b[3]["type"] = "s";
			$b[3]["value"] = @serialize(@$attachments);
			$b[4]["type"] = "s";
			$b[4]["value"] = @$subject;
			$b[5]["type"] = "s";
			$b[5]["value"] = @serialize(@$content);
			$b[6]["type"] = "s";
			$b[6]["value"] = @serialize(@$debug_message);
			$this->l_mysql->query("INSERT INTO `".$this->l_table."`(receiver, bcc, cc, attach, subject, msgtext, success, debugmsg, section) VALUES(?, ?, ?,?,?,?, '".$success."', ?, '".$this->l_section."');", $b);
			return true; } return true;
		}

		// Send Mail Function Method #1
		public function send($to, $toname, $title, $mailContent, $ishtml = false, $FOOTER = false, $HEADER = false, $attachments = false) {
			// Create Object PHPMailer
			$tmp_mailer = new PHPMailer;
			
			// Buildup Connection
			$tmp_mailer->isSMTP();
			$tmp_mailer->Host 		   = $this->host;
			$tmp_mailer->SMTPAuth 	   = $this->smtpauth;
			$tmp_mailer->Username 	   = $this->username;
			$tmp_mailer->Password 	   = $this->password; 
			$tmp_mailer->SMTPSecure    = $this->smtpsecure;
			$tmp_mailer->Port 		   = $this->port;	
			$tmp_mailer->SMTPKeepAlive = $this->keep_alive;
			$tmp_mailer->SMTPDebug     = $this->smtpdebuglevel;
			$tmp_mailer->CharSet 	   = $this->charset;
			$tmp_mailer->Encoding 	   = $this->encoding;			
			//$tmp_mailer->AuthType = 'PLAIN';			
			
			// Activate Default HTML if needed
			if($this->html AND !$ishtml) { $tmp_mailer->isHTML($this->html); } else { $tmp_mailer->isHTML($ishtml); }
			
			// Activate Insecure Connections
			if($this->allow_insecure_connection) {$tmp_mailer->SMTPOptions = ['ssl' => ['verify_peer' => false,'verify_peer_name' => false,'allow_self_signed' => true]];}
			
			// Set From Variables
			$tmp_mailer->setFrom($this->setFromMail, $this->setFromName);
			
			// Set Reply To Variables
			$tmp_mailer->addReplyTo($this->addReplyToMail, $this->addReplyToName);
			
			// Adress to Send Test-Mode if Set otherwhise set Real Receivers Adr.
			if( is_string($this->test_mode) ) { 
				$tmp_mailer->addAddress($this->test_mode); 	
			} else {
				if(is_array($to)) { foreach ($to as &$value) {$tmp_mailer->addAddress($value["mail"], $value["name"]);}
				} else {$tmp_mailer->addAddress($to, $toname);}	
			}			

			// Add Attachments
			if(is_array($attachments)) { foreach ($attachments as &$value) {$tmp_mailer->addAttachment($value);}
			} else { if(is_string($attachments)) { $tmp_mailer->addAttachment($attachments); }}
			
			// Set the Title for Mail
			$tmp_mailer->Subject = $title;			
			
			// Prepare Content with Footer and Header
			$xFOOTER = "";
			$xHEADER = "";
			if(!$FOOTER) {$xFOOTER = $this->footer;}
			if(!$HEADER) {$xHEADER = $this->header;}
			$realcontent = $xHEADER.$mailContent.$xFOOTER;
			$tmp_mailer->Body = $realcontent;			

			// Send the Mail
			if($mail_status = $tmp_mailer->send()){		
				// Mail Sending Success
				$this->log_execute($title, $realcontent, $to, $attachments, array(), array(), true, $tmp_mailer->ErrorInfo, false);				
				$this->set_info($tmp_mailer->ErrorInfo);
				unset($tmp_mailer);
				return true;
			} else {
				// Mail Sending Fail
				$this->log_execute($title, $realcontent, $to, $attachments, array(), array(), true, $tmp_mailer->ErrorInfo, false);	
				$this->set_info($tmp_mailer->ErrorInfo);
				unset($tmp_mailer);
				return false;
			}
		}
		
		// Advanced Mail Functions #2
		public function mail($subject, $content, $receiver, $cc, $bcc, $attachment, $settings = array()) {
			// Create Object PHPMailer
			$tmp_mailer = new PHPMailer;
			$tmp_mailer->isSMTP();
		
			// Write SMTP Settings from Class or Settings Override Array		
			if(!is_string($settings["host"])) 			{$tmp_mailer->Host 			= $this->host; 			} else { $tmp_mailer->Host 			= $settings["host"];}
			if(!isset($settings["smtpauth"])) 		{$tmp_mailer->SMTPAuth 	    = $this->smtpauth; 		} else { $tmp_mailer->SMTPAuth 		= $settings["smtpauth"];}
			if(!is_string($settings["username"])) 		{$tmp_mailer->Username 	    = $this->username;		} else { $tmp_mailer->Username 		= $settings["username"];}
			if(!is_string($settings["password"])) 		{$tmp_mailer->Password 	    = $this->password;  	} else { $tmp_mailer->Password 		= $settings["password"];}		
			if(!isset($settings["smtpsecure"])) 	{$tmp_mailer->SMTPSecure    = $this->smtpsecure; 	} else { $tmp_mailer->SMTPSecure 	= $settings["smtpsecure"]; }
			if(!is_numeric($settings["port"])) 			{$tmp_mailer->Port 		    = $this->port;	 		} else { $tmp_mailer->Port 			= $settings["port"]; }
			if(!is_bool($settings["keep_alive"])) 	{$tmp_mailer->SMTPKeepAlive = $this->keep_alive; 	} else { $tmp_mailer->SMTPKeepAlive = $settings["keep_alive"]; }		
			if(!is_string($settings["charset"])) 		{$tmp_mailer->CharSet 	   	= $this->charset; 		} else { $tmp_mailer->CharSet 		= $settings["charset"]; }
			if(!is_string($settings["encoding"])) 		{$tmp_mailer->Encoding 		= $this->encoding; 		} else { $tmp_mailer->Encoding 		= $settings["encoding"]; }		
			if(!is_bool($settings["html"])) 			{$tmp_mailer->isHTML($this->html); 					} else { $tmp_mailer->isHTML($settings["html"]); }		
			if(!is_numeric($settings["smtpdebuglevel"])) 		{$tmp_mailer->SMTPDebug     = $this->smtpdebuglevel; 	} else { $tmp_mailer->SMTPDebug 	= $settings["smtpdebuglevel"]; }
			if($settings["allow_insecure_connection"] !== false) {
				if($this->allow_insecure_connection) {				$tmp_mailer->SMTPOptions = [
				  'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				  ]
				];}
			} else { if($settings["allow_insecure_connection"]) { $tmp_mailer->SMTPOptions = [
				  'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				  ]
				]; }  }		
		
			// Set the Title for Mail
			$tmp_mailer->Subject = $subject;		
		
			// Prepare The Content
			if(!isset($settings["footer"])) { $FOOTER = $this->footer; } else { $FOOTER = $settings["footer"]; }		
			if(!isset($settings["header"])) { $HEADER = $this->header; } else { $HEADER = $settings["header"]; }		

			// Add Content To Mail
			$tmp_mailer->Body = $HEADER.$content.$FOOTER;

			// Sender
			if(is_array($settings["sender"])) { 
				if(isset($settings["sender"][0]) AND isset($settings["sender"][1])) {
					$tmp_mailer->setFrom($settings["sender"][0], $settings["sender"][1]);
				} elseif(isset($settings["sender"][1])) {
					$tmp_mailer->setFrom($settings["sender"][0]);
				} else {
					$tmp_mailer->setFrom($this->setFromMail, $this->setFromName);
				}
			} else {
				$tmp_mailer->setFrom($this->setFromMail, $this->setFromName);
			}			
			// Reply To
			if(is_array($settings["replyto"])) { 
				if(isset($settings["replyto"][0]) AND isset($settings["replyto"][1])) {
					$tmp_mailer->addReplyTo($settings["replyto"][0], $settings["replyto"][1]);
				} elseif(isset($settings["replyto"][1])) {
					$tmp_mailer->addReplyTo($settings["replyto"][0]);
				}
			} else {
				$tmp_mailer->addReplyTo($this->addReplyToMail, $this->addReplyToName);
			}	
			// Handler Receivers
			if($this->test_mode != false) { $tmp_mailer->addAddress($this->test_mode); }
			elseif(is_array($receiver)) { 
				foreach($receiver AS $key => $value) {
					if(is_string($value[1])) { $tmp_mailer->addAddress($value[0], $value[1]); }
					elseif(is_string($value[0])) { $tmp_mailer->addAddress($value[0]); }
				}			
			}
			// Handler CC
			if(is_array($cc) AND $this->test_mode == false) { 
				foreach($cc AS $key => $value) {
					if(is_string($value[1])) { $tmp_mailer->addCC($value[0], $value[1]); }
					elseif(is_string($value[0])) { $tmp_mailer->addCC($value[0]); }
				}			
			}		
			// Handler BCC
			if(is_array($bcc) AND $this->test_mode == false) { 
				foreach($bcc AS $key => $value) {
					if(is_string($value[1])) { $tmp_mailer->addBCC($value[0], $value[1]); }
					elseif(is_string($value[0])) { $tmp_mailer->addBCC($value[0]); }
				}			
			}	
			// Handler Attachment
			if(is_array($attachment)) { 
				foreach($attachment AS $key => $value) {
					if(is_string($value[1])) { $tmp_mailer->addAttachment($value[0], $value[1]); }
					elseif(is_string($value[0])) { $tmp_mailer->addAttachment($value[0]); }
				}			
			}				

			// Send the Mail
			if($mail_status = $tmp_mailer->send()){		
				// Mail Sending Success
				$this->log_execute($subject, $HEADER.$content.$FOOTER, $receiver, $attachment, $cc, $bcc, true, $tmp_mailer->ErrorInfo, $settings);				
				$this->set_info($tmp_mailer->ErrorInfo);
				unset($tmp_mailer);
				return true;
			} else {
				// Mail Sending Fail
				$this->log_execute($subject, $HEADER.$content.$FOOTER, $receiver, $attachment, $cc, $bcc, true, $tmp_mailer->ErrorInfo, $settings);	
				$this->set_info($tmp_mailer->ErrorInfo);
				unset($tmp_mailer);
				return false;
			}
		} 
		
		public function object() {
			return new x_class_mail_item($this);
		}
	}
	
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/  x_class_mail_item Control Class */	
	// Object for Sending Mails to Locations, x_class_mail needed
	class x_class_mail_item {
		// Class Variables
		private $x_class_mail = false;
		// Construct		
		function __construct($x_class_mail) {$this->x_class_mail = $x_class_mail;} 
		// Functions for Send Adjustments
		private $attachment = array();
		public function add_attachment($path, $name) { array_push($this->attachment, array($path,$name)); }
		public function get_attachment() { return $this->attachment; }	
		public function clear_attachment() { $this->attachment = array(); }
		private $receiver = array();
		public function add_receiver($mail, $name) { array_push($this->receiver, array($mail,$name)); }
		public function get_receiver() { return $this->receiver; }	
		public function clear_receiver() { $this->receiver = array(); }
		private $cc = array();
		public function add_cc($mail, $name) { array_push($this->cc, array($mail,$name)); }
		public function get_cc() { return $this->cc; }
		public function clear_cc() { $this->cc = array(); }
		private $bcc = array();
		public function add_bcc($mail, $name) { array_push($this->bcc, array($mail,$name)); }	
		public function get_bcc() { return $this->bcc; }		
		public function clear_bcc() { $this->bcc = array(); }
		private $settings = array();
		public function add_setting($name, $value) { $this->settings[$name] = $value; }	
		public function get_setting() { return $this->settings; }
		public function clear_setting() { $this->settings = array(); }		
		######################################################################################################################################
		// Send Final Mail
		public function send($subject, $content) {
			return $this->x_class_mail->mail($subject, $content, $this->receiver, $this->cc, $this->bcc, $this->attachment, $this->settings);
		}
	}
