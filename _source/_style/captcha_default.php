<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  Captcha File */
	require_once("../settings.php");
	x_captcha(_COOKIES_."captcha_default", _CAPTCHA_WIDTH_, _CAPTCHA_HEIGHT_, _CAPTCHA_SQUARES_, _CAPTCHA_ELIPSE_, false, _CAPTCHA_FONT_, _CAPTCHA_RANDOM_);
?>  