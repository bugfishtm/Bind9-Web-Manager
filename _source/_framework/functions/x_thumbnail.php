<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ X Function Set	*/
	
	###################################
	### Create Thumbnail from URL   ###
		function x_thumbnail($url, $filename, $width = 600, $height = true) {
		 $image = ImageCreateFromString(file_get_contents($url));
		 $height = $height === true ? (ImageSY($image) * $width / ImageSX($image)) : $height;
		 $output = ImageCreateTrueColor($width, $height);
		 ImageCopyResampled($output, $image, 0, 0, 0, 0, $width, $height, ImageSX($image), ImageSY($image));
		 ImageJPEG($output, $filename, 95); 
		 return $output; }
		 
	###########################################
	### Create Thumbnail from URL to File   ###		 
	//create_thumbnail_from_url('https://example.com/image.png', 100, 100, '/path/to/thumbnail.png');
		function x_thumbnail_save($url,  $save_path = null, $width = 600, $height = true) {
		  $image = imagecreatefrompng($url);
		  $thumbnail = imagecreatetruecolor($width, $height);
		  imagecopyresized($thumbnail, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
		  imagedestroy($image);

		  if ($save_path !== null) {
			imagepng($thumbnail, $save_path);
		  }
		  
		  imagedestroy($thumbnail);
		  return true;
		}