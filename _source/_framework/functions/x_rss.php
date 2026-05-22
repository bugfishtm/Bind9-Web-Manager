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
	// Directly output RSS in Sections
	########################################################################
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
			$title = htmlspecialchars($feed[$x]['title'] ?? '');
			$link = $feed[$x]['link'];
			$cover = $feed[$x]['img'];
			$date = date('Y/m/d H:i:s', strtotime($feed[$x]['date']));
			if($cover == NULL OR trim($cover ?? '') == "") { $cover = $defaultcover;}
			echo '<div class="x_rss_item" onclick="location.href = \''.$link.'\';" >';
			if(is_string($cover)) { if( substr($cover, 0, 4) != "http") { $cover = "https://".$cover; } }
			if(trim($cover ?? '') != "" AND $cover != NULL AND strlen($cover) > 7) {echo '<img class="x_rss_image" src="'.$cover.'">';}
			echo '<section class="x_rss_title" title="'.$title.'">'.$title.'</section>';
			echo '<section class="x_rss_date">Posted on '.$date.'</section><br clear="left"></div>';}
		}
	}
	
	########################################################################
	// Get RSS List in Array
	########################################################################
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