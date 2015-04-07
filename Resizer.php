<?php

require 'FileSystem.php';
require 'cache.php';

class Resizer {

    private $path;
    private $configuration;
    private $cache;
    private $fileSystem;

    public function __construct($path, $configuration) {
        $this->checkPath($path);
        $this->checkConfiguration($configuration);
        $this->path = $path;
        $this->configuration = $configuration;
        $this->fileSystem = new FileSystem();
        $this->cache = new Cache($configuration->obtainCacheMinutes());
    }

    public function injectFileSystem(FileSystem $fileSystem) {
        $this->fileSystem = $fileSystem;
        $this->cache->injectFileSystem($fileSystem);
        $this->path->injectFileSystem($fileSystem);
    }

    public function obtainFilePath() {
        $imagePath = '';

        if($this->path->isFileExternal()):
//             $filename = $this->path->obtainFileName();
//             $local_filepath = $this->configuration->obtainRemote() .$filename;
	    $cacheRemotePath = $this->configuration->obtainRemote();
	    $local_filepath = $this->path->obtainFilePathLocal($cacheRemotePath);
            $inCache = $this->cache->isInCache($local_filepath);

            if(!$inCache):
                $this->cache->download($this->path->sanitizedPath(), $local_filepath);
            endif;
            $imagePath = $local_filepath;
        endif;

	if (!$this->cache->checkFileInLocal($imagePath)) {	
	    throw new RuntimeException('image not found');
	}

        return $imagePath;
    }
    
    private function checkPath($path) {
        if (!($path instanceof ImagePath)) throw new InvalidArgumentException();
    }

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }    
        
    public function isNecessaryNewFile($newFile,$cacheFile) {	    
    
	return $this->cache->isNecessaryNewFile($newFile,$cacheFile);
    }
    
    public function composeNewPath() {
	return $this->path->composeNewPath($this->obtainFilePath(),$this->configuration);
    
/*	    $filename = md5_file($this->obtainFilePath());
	    $widthSignal = $this->obtainSignalWidth();
	    $heightSignal = $this->obtainSignalHeight();
	    $cropSignal = $this->obtainSignalCrop();
	    $scaleSignal = $this->obtainSignalScale();
	    $extension = '.' . $this->path->obtainFileExtension();

	    $newPath = $this->configuration->obtainCache().$filename.$widthSignal.$heightSignal.$cropSignal.$scaleSignal.$extension;

        $outputFilename = $this->configuration->obtainOutputFilename();
        if ($outputFilename) {        
		    $newPath = $outputFilename;
	    }

	    return $newPath;*/               
    }   
    
//     private function obtainSignalCrop() {
//         $signalCrop = "";
//         if ($this->configuration->obtainCrop() == true) {            
//             $signalCrop = "_cp";
//         }
//         
//         return $signalCrop;
//     }
// 
//     private function obtainSignalScale() {
//         $signalScale = "";
//         if ($this->configuration->obtainScale() == true) {            
//             $signalScale = "_sc";
//         }
//         
//         return $signalScale;
//     }        
//     
//     private function obtainSignalHeight() {
//         $signalHeight = "";
// 	    $height = $this->configuration->obtainHeight();        
//         
//         if (!empty($height)) {            
//             $signalHeight = "_h".$height;
//         }
//         
//         return $signalHeight;
//     }    
// 
//     private function obtainSignalWidth() {
//         $signalWidth = "";
// 	    $width = $this->configuration->obtainWidth();        
//         
//         if (!empty($width)) {            
//             $signalWidth = "_w".$width;
//         }
//         
//         return $signalWidth;
//     }
 
    public function doResize() {
        $imagePath = escapeshellarg($this->obtainFilePath());
        $newPath = escapeshellarg($this->composeNewPath());     	
        if ($this->isNecessaryNewFile($imagePath,$newPath) == false) {
            return $this->obtainCacheFilePath();
        }    

        $cmd = $this->obtainCommand(); 
	$exec = exec($cmd, $output, $return_code);
	
	if($return_code != 0) {
	    error_log("Tried to execute : $cmd, return code: $return_code, output: " . print_r($output, true));
	    throw new RuntimeException('cannot resize the image');
	}
	
	return $this->obtainCacheFilePath();
    }
    
    public function obtainCommand() {        
	$width = $this->configuration->obtainWidth();
	$height = $this->configuration->obtainHeight();
	$commandWithDimensions = (!empty($width) && !empty($height));        
	$scale = $this->configuration->obtainScale();	        
        
        if($commandWithDimensions == true && $scale == true) {
            $cmd = $this->commandWithScale();
        }
        
        if($commandWithDimensions == true && $scale == false) {
            $cmd = $this->commandWithCrop();
        }    
        
        if($commandWithDimensions == false) {        
            $cmd = $this->defaultShellCommand();
        }
        
        return $cmd;    
    }       
    
    public function defaultShellCommand() {        
        $imagePath = escapeshellarg($this->obtainFilePath());
        $newPath = escapeshellarg($this->composeNewPath());

        $command = $this->configuration->obtainConvertPath() . " " . $imagePath . " ".
                    $this->obtainCmdArgumentThumbnail() .
                    $this->obtainCmdArgumentMaxOnly() . " " .
                    $this->obtainCmdArgumentQuality() . " " . $newPath;                   

	return $command;
    }
    
    public function commandWithScale() {		    
        $imagePath = escapeshellarg($this->obtainFilePath());
        $newPath = escapeshellarg($this->composeNewPath());
		    
	$command = $this->configuration->obtainConvertPath() . " " . $imagePath . " " .
		    $this->obtainCmdArgumentResize($imagePath) . " " .
		    $this->obtainCmdArgumentQuality() . " " . $newPath;                   

	return $command;
    }    
    
    public function commandWithCrop() {
        $imagePath = escapeshellarg($this->obtainFilePath());
        $newPath = escapeshellarg($this->composeNewPath());

        $command = $this->configuration->obtainConvertPath() . " " . $imagePath . " " .
                    $this->obtainCmdArgumentResize($imagePath) . " " .
                    $this->obtainCmdArgumentSize() . " " .
                    $this->obtainCmdArgumentCanvasColor() . " " .
                    $this->obtainCmdArgumentsForCrop() . " " .
                    $this->obtainCmdArgumentQuality() . " " . $newPath;                  

	return $command;
    }      
    
    private function obtainCmdArgumentThumbnail() {
      	$width = $this->configuration->obtainWidth();
      	$height = $this->configuration->obtainHeight();
	$separator = "";      	
	if (!empty($height)) {
	    $separator = "x";       
	}	    
	$argumentCommand = "-thumbnail ". $separator . $width;
               
        return $argumentCommand;
    }   
    
    private function obtainCmdArgumentMaxOnly() {
      	$maxOnly = $this->configuration->obtainMaxOnly();      	
	$argumentCommand = "";
	if (isset($maxOnly) && $maxOnly == true) {
	    $argumentCommand = "\>";       
	}	                   
	
        return $argumentCommand;
    }       
    
    private function obtainCmdArgumentQuality() {
      	$quality = escapeshellarg($this->configuration->obtainQuality());      	
	$argumentCommand = "-quality " . $quality;
    
        return $argumentCommand;
    }
    
    private function obtainCmdArgumentResize() {   
        $imagePath = escapeshellarg($this->obtainFilePath());    
    
	$resize = escapeshellarg($this->composeResizeOptions($imagePath));  
	$argumentCommand = "-resize ". $resize; 
	
	return $argumentCommand;
    }
    
    private function composeResizeOptions($imagePath) {
	$width = $this->configuration->obtainWidth();
	$height = $this->configuration->obtainHeight();
	$hasCrop = ($this->configuration->obtainCrop() == true);
	$isPanoramic = $this->configuration->isPanoramic($imagePath);

        $isPanoramicWithoutCrop = $isPanoramic && !$hasCrop;
        $NotIsPanoramicWithCrop = !$isPanoramic && $hasCrop; 

        $resize = "x".$height;

	if($isPanoramicWithoutCrop) {
		$resize = $width;
	}

	if($NotIsPanoramicWithCrop) {
		$resize = $width;
	}

	return $resize;
    }     
    
    private function obtainCmdArgumentSize() {
      	$width = $this->configuration->obtainWidth();
      	$height = $this->configuration->obtainHeight();            
      	$size = escapeshellarg($width ."x". $height);
        $argumentCommand = "-size " . $size;
 
 	return $argumentCommand;    
    }

    private function obtainCmdArgumentCanvasColor() {
      	$canvasColor = escapeshellarg($this->configuration->obtainCanvasColor());        
        $argumentCommand = "xc:". $canvasColor;
 
 	return $argumentCommand;    
    }    
    
    private function obtainCmdArgumentsForCrop() {
    
        return "+swap -gravity center -composite"; 
    }
    
    public function obtainCacheFilePath() {
        $newPath = escapeshellarg($this->composeNewPath());
        $filePathRelative = str_replace($_SERVER['DOCUMENT_ROOT'],'',$newPath);       
        
        return $filePathRelative;
    }               
}
