<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ Json Struct Data Library	*/
	function x_structdata_article($publisher_name, $publisher_logo, $publisher_website, $image, $url, $title, $published_date, $modified_date) {			
		echo '<script type="application/ld+json">{
			  "@context": "https://schema.org",
			  "@type": "NewsArticle",
			  "mainEntityOfPage": {
				"@type": "WebPage",
				"@id": "'.$url.'"},
			  "headline": "'.$title.'",
			  "image": [
				"'.$image.'"],
			  "datePublished": "'.$published_date.'",
			  "dateModified": "'.$modified_date.'",
			  "author": {
				"@type": "Person",
				"name": "'.$publisher_name.'",
				"url": "'.$publisher_website.'"},
			  "publisher": {"@type": "Organization","name": "'.$publisher_name.'","logo": {
				  "@type": "ImageObject",
				  "url": "'.$publisher_logo.'"}}}
			</script>';	
	}
	
	
	function x_structdata_websoftware($publisher_name, $publisher_logo, $publisher_website, $image, $url, $title, $published_date, $modified_date) {	
		echo '<script type="application/ld+json">{"@context": "https://schema.org","@type": "SoftwareApplication",
			  "name": "'.$title.'","operatingSystem": ["ANDROID", "WINDOWS", "OSX"],"applicationCategory": "BrowserApplication",
			  "headline": "'.$title.'","mainEntityOfPage": {"@type": "WebPage",
				"@id": "'.$url.'"},
			  "image": [
				"'.$image.'"],
			  "offers": {"@type": "Offer","price": "0","priceCurrency": "EUR"},
			  "datePublished": "'.$published_date.'", 
			  "dateModified": "'.$modified_date.'",
			  "author": {"@type": "Person",
				"name": "'.$publisher_name.'",
				"url": "'.$publisher_website.'"},
			  "publisher": {"@type": "Organization","name": "'.$publisher_name.'",
				"logo": {"@type": "ImageObject","url": "'.$publisher_logo.'"}}}</script>';						
	}

