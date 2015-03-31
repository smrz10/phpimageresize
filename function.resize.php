<?php

require 'ImagePath.php';
require 'Configuration.php';
require 'Resizer.php';

function composeResizeOptions($imagePath, $configuration) {
	$opts = $configuration->asHash();
	$w = $configuration->obtainWidth();
	$h = $configuration->obtainHeight();

	$resize = "x".$h;

	$hasCrop = (true === $opts['crop']);

	if(!$hasCrop && isPanoramic($imagePath)):
		$resize = $w;
	endif;

	if($hasCrop && !isPanoramic($imagePath)):
		$resize = $w;
	endif;

	return $resize;
}

function commandWithScale($imagePath, $newPath, $configuration) {
	$opts = $configuration->asHash();
	$resize = composeResizeOptions($imagePath, $configuration);

	$cmd = $configuration->obtainConvertPath() ." ". escapeshellarg($imagePath) ." -resize ". escapeshellarg($resize) .
		" -quality ". escapeshellarg($opts['quality']) . " " . escapeshellarg($newPath);

	return $cmd;
}

function commandWithCrop($imagePath, $newPath, $configuration) {
	$opts = $configuration->asHash();
	$w = $configuration->obtainWidth();
	$h = $configuration->obtainHeight();
	$resize = composeResizeOptions($imagePath, $configuration);

	$cmd = $configuration->obtainConvertPath() ." ". escapeshellarg($imagePath) ." -resize ". escapeshellarg($resize) .
		" -size ". escapeshellarg($w ."x". $h) .
		" xc:". escapeshellarg($opts['canvas-color']) .
		" +swap -gravity center -composite -quality ". escapeshellarg($opts['quality'])." ".escapeshellarg($newPath);

	return $cmd;
}

function doResize($imagePath, $newPath, $configuration) {
	$opts = $configuration->asHash();
	$w = $configuration->obtainWidth();
	$h = $configuration->obtainHeight();

	if(!empty($w) and !empty($h)):
		$cmd = commandWithCrop($imagePath, $newPath, $configuration);
		if(true === $opts['scale']):
			$cmd = commandWithScale($imagePath, $newPath, $configuration);
		endif;
	else:
		$cmd = defaultShellCommand($configuration, $imagePath, $newPath);
	endif;

	$c = exec($cmd, $output, $return_code);
	if($return_code != 0) {
		error_log("Tried to execute : $cmd, return code: $return_code, output: " . print_r($output, true));
		throw new RuntimeException();
	}
}

function resize($imagePath,$opts=null){
	$path = new ImagePath($imagePath);
	
	try {
	    $configuration = new Configuration($opts);
	} catch (Exception $e) {
	    return 'cannot resize the image';
	}
	
	$resizer = new Resizer($path, $configuration);

	// This has to be done in resizer resize

	try {
		$imagePath = $resizer->obtainFilePath();
	} catch (Exception $e) {
		return 'image not found';
	}


	$newPath = composeNewPath($imagePath, $configuration);

    $create = !isInCache($newPath, $imagePath);

	if($create == true):
		try {
			doResize($imagePath, $newPath, $configuration);
		} catch (Exception $e) {
			return 'cannot resize the image';
		}
	endif;

	// The new path must be the return value of resizer resize

	$cacheFilePath = str_replace($_SERVER['DOCUMENT_ROOT'],'',$newPath);

	return $cacheFilePath;
	
}
