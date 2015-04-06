<?php

require 'ImagePath.php';
require 'Configuration.php';
require 'Resizer.php';

function resize($imagePath,$opts=null){	
	try {
	    $configuration = new Configuration($opts);
	} catch (Exception $e) {
	    return 'cannot resize the image';
	}
	
	$path = new ImagePath($imagePath);	
	$resizer = new Resizer($path, $configuration); 

	try {
		$cacheFilePath = $resizer->doResize();
	} catch (Exception $e) {
		//return 'cannot resize the image';
		return $e->getMessage();
	}

	return $cacheFilePath;	
}
