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

/* Function to get GET Parameters Value */
	function xjs_get(parameterName) {
		var result = null, tmp = [];
		location.search.substr(1).split("&") .forEach(function (item) {
		  tmp = item.split("="); if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]); });
		return result;}
		
/* Search for A String in URL / True if Found / False if Not */
	function xjs_in_url(parameterName) {return window.location.href.includes(parameterName);}

/* Hide or Show Object with ID */
	function xjs_hide_id(id) 	{id.css("display", "none");}
	function xjs_show_id(id) 	{id.css("display", "block");}
	function xjs_toggle_id(id) 	{if(id.css("display") != "none") { id.css("display", "none"); } else { id.css("display", "block"); } }

/** Check if a Mail is valid **/
	function xjs_is_email(email)  { var re = /\S+@\S+\.\S+/; return re.test(email); }

/** Create A Dynamic PopUp with X Button to Close **/
	function xjs_popup(var_text, var_entrie = "Close") { 
		var_output = "<div id='xjs_popup'><div id='xjs_popup_inner'>"+var_text;
		if(var_entrie) { var_output = var_output+"<div id='xjs_popup_close' onclick='document.getElementById(\"xjs_popup\").remove();'>"+var_entrie+"</div>"; }
		var_output = var_output+"</div></div>";
		document.body.insertAdjacentHTML('beforeend', var_output);}

/** Generate Passwords **/
	function xjs_genkey(length = 12, charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789") { retVal = ""; for (var i = 0, n = charset.length; i < length; ++i) {retVal += charset.charAt(Math.floor(Math.random() * n));} return retVal;}

