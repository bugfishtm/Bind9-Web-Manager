<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ X Function Set	*/
				
	//////////////////////////////////////////////
	// RSS Functions
	//////////////////////////////////////////////
	
	function x_rss_list($urltemp, $defaultcover, $limit = 25) {
		$rss = new DOMDocument();$rss->load($urltemp);$feed = array();
		foreach ($rss->getElementsByTagName('item') as $node) {
			$item = array ('title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
				'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
				'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue,
				'img' => $node->getElementsByTagName('enclosure')->item(0)->getAttribute('url'),);
			array_push($feed, $item);}
		for($x=0;$x<$limit;$x++) {
			if(@$feed[$x]['title']) {
			$title = htmlspecialchars($feed[$x]['title']);
			$link = $feed[$x]['link'];
			$cover = $feed[$x]['img'];
			$date = date('Y/m/d H:i:s', strtotime($feed[$x]['date']));
			if($cover == NULL OR trim($cover) == "") { $cover = $defaultcover;}
			echo '<div class="x_rss_item" onclick="location.href = \''.$link.'\';" >';
			if(is_string($cover)) { if( substr($cover, 0, 4) != "http") { $cover = "https://".$cover; } }
			if(trim($cover) != "" AND $cover != NULL AND strlen($cover) > 7) {echo '<img class="x_rss_image" src="'.$cover.'">';}
			echo '<section class="x_rss_title" title="'.$title.'">'.$title.'</section>';
			echo '<section class="x_rss_date">Posted on '.$date.'</section><br clear="left"></div>';}
		}
	}

	function x_rss_array($urltemp) {
		$rss = new DOMDocument();$rss->load($urltemp);$feed = array();
		foreach ($rss->getElementsByTagName('item') as $node) {
			$item = array ('title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
				'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
				'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue,
				'img' => $node->getElementsByTagName('enclosure')->item(0)->getAttribute('url'),);
			array_push($feed, $item);}
		return $feed;
	}

