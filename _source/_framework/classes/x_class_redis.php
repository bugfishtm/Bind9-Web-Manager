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
	class x_class_redis {
		private $redis  	= false; 		
		private $pre  	= false; 		
		
		function __construct($host, $port, $pre = "") { $redis = new Redis(); try { if($redis->connect($host, $port)) { $this->redis = $redis; } else { $this->redis = false; error_log("Redis could not connect with x_class_redis!"); } } catch (\Exception $e) {  $this->redis = false; error_log("Redis could not connect with x_class_redis!");} $this->pre = $pre; }
		
		public function valid() { if ($this->redis) { return true; } else { return false; }  }
		
		public function redis() { if ($this->redis) { return $this->redis; } else { return false; }  }
		public function ping() { if ($this->redis) { return $this->redis->ping(); } else { return false; } }
		public function keys($pre = false, $after = "") { if(!$pre) {$pre = $this->pre;}if ($this->redis) { return $this->redis->keys(@$pre."*".$after); } else { return false; } }
		
		public function add_string($name, $value) { 
			if ($this->redis) {
				if(is_string($value) AND is_string($name)) {
					return $this->redis->set($this->pre.$name, $value); 
				} return false;
			} return false;
		}
		public function add_list($name, $value) { 
			if ($this->redis) {
				if(is_array($value) AND is_string($name)) {
					foreach($value AS $key =>$valuex) {
						$this->redis->lpush($this->pre.$name, $valuex); 
					}
				}
			} return false;
		}
		public function get_string($name) { 
			if ($this->redis) {
				if(is_string($name)) {
					return $this->redis->get($this->pre.$name); 
				} return false;
			} return false;
		}
		public function get_list($name, $start, $end) { 
			if ($this->redis) {
				if(is_string($name)) {
					 return $redis->lrange($this->pre.$name, $start , $end); 
				} return false;
			} return false;
		}
	}
