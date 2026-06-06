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
	
	########################################################################
	// Generate a Captcha Image
	########################################################################
	function x_captcha($preecookie = "", $width = 550, $height = 250, $square_count = 5, $eclipse_count = 5, $color_ar = false, $font = "", $code = "") {
		if (session_status() === PHP_SESSION_NONE) { session_start(); }
		
		if(!is_array($color_ar)) {
			$color_ar = array();
			$color_ar["squares"] = array();
			$color_ar["squares"]["r"] = 255;
			$color_ar["squares"]["g"] = 255;
			$color_ar["squares"]["b"] = 255;
			$color_ar["eclipse"]["r"] = 244;
			$color_ar["eclipse"]["g"] = 244;
			$color_ar["eclipse"]["b"] = 244;
			$color_ar["background"]["r"] = 24;
			$color_ar["background"]["g"] = 24;
			$color_ar["background"]["b"] = 24;
			$color_ar["text"]["r"] = 255;
			$color_ar["text"]["g"] = 255;
			$color_ar["text"]["b"] = 255;
		}
	
		$im = imagecreatetruecolor($width, $height); 
			
		$c_square  	 = imagecolorallocate($im, $color_ar["squares"]["r"], $color_ar["squares"]["g"], $color_ar["squares"]["b"]);
		$c_ellipse   = imagecolorallocate($im, $color_ar["eclipse"]["r"], $color_ar["eclipse"]["g"], $color_ar["eclipse"]["b"]);
		$background	 = imagecolorallocate($im, $color_ar["background"]["r"], $color_ar["background"]["g"], $color_ar["background"]["b"]);
		$c_txt  	 = imagecolorallocate($im, $color_ar["text"]["r"], $color_ar["text"]["g"], $color_ar["text"]["b"]);
			
		header("Expires: Mon, 21 Jul 2020 05:00:00 GMT");   
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");   
		header("Cache-Control: no-store, no-cache, must-revalidate");   
		header("Cache-Control: post-check=0, pre-check=0", false);  
		header("Pragma: no-cache");   
		   
		imagefilledrectangle($im, 0, 0, 399, 29, $background);
		
		for($i = 0; $i < $square_count; $i++){
			$cx = rand(0,$width);
			$cy = (int)rand(0, $width/2);
			$h  = $cy + (int)rand(0, $height/5);
			$w  = $cx + @(int)rand($width/3, $width);
			imagefilledrectangle($im, $cx, $cy, $w, $h, $c_square);}

		for ($i = 0; $i < $eclipse_count; $i++) {
		  $cx = (int)rand(-1*($width/2), $width + ($width/2));
		  $cy = (int)rand(-1*($height/2), $height + ($height/2));
		  $h  = (int)rand($height/2, 2*$height);
		  $w  = (int)rand($width/2, 2*$width);
		  imageellipse($im, $cx, $cy, $w, $h, $c_ellipse);}
		 
		$_SESSION[$preecookie] = strval($code);  
		imagefttext($im, 40, 20, 10, 60, $c_txt, $font, $_SESSION[$preecookie][0]);
		imagefttext($im, 40, 20, 60, 60, $c_txt, $font, $_SESSION[$preecookie][1]);
		imagefttext($im, 40, 20, 110, 60, $c_txt, $font, $_SESSION[$preecookie][2]);
		imagefttext($im, 40, 20, 150, 60, $c_txt, $font, $_SESSION[$preecookie][3]);
		Header ('Content-type: image/jpeg');  
		imagejpeg($im,NULL,100);  
		ImageDestroy($im);  		
	}

	########################################################################
	// Get the Current Captcha Key for a Cookie Variable
	########################################################################
	function x_captcha_key($preecookie = "") { return $_SESSION[$preecookie];	}