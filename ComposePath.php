<?php

class ComposePath {
    
    private $imagePath;
    private $fileExtension;
    private $configuration;

    public function __construct($imagePath, $fileExtension, $configuration) {
        $this->checkConfiguration($configuration);
        
	$this->imagePath = $imagePath;
	$this->fileExtension = $fileExtension;
        $this->configuration = $configuration;	
    }

    public function composeNewPath() {
	$filename = md5_file($this->imagePath);
	$widthSignal = $this->obtainSignalWidth();
	$heightSignal = $this->obtainSignalHeight();
	$cropSignal = $this->obtainSignalCrop();
	$scaleSignal = $this->obtainSignalScale();
	$extension = '.' . $this->fileExtension;

	$newPath = $this->configuration->obtainCache().$filename.$widthSignal.$heightSignal.$cropSignal.$scaleSignal.$extension;

        $outputFilename = $this->configuration->obtainOutputFilename();
        if ($outputFilename) {        
	    $newPath = $outputFilename;
	}

	return $newPath;               
    }   
    
    private function obtainSignalCrop() {
        $signalCrop = "";
        if ($this->configuration->obtainCrop() == true) {            
            $signalCrop = "_cp";
        }
        
        return $signalCrop;
    }

    private function obtainSignalScale() {
        $signalScale = "";
        if ($this->configuration->obtainScale() == true) {            
            $signalScale = "_sc";
        }
        
        return $signalScale;
    }        
    
    private function obtainSignalHeight() {
        $signalHeight = "";
	$height = $this->configuration->obtainHeight();        
        
        if (!empty($height)) {            
            $signalHeight = "_h".$height;
        }
        
        return $signalHeight;
    }    

    private function obtainSignalWidth() {
        $signalWidth = "";
	$width = $this->configuration->obtainWidth();        
        
        if (!empty($width)) {            
            $signalWidth = "_w".$width;
        }
        
        return $signalWidth;
    }

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }
}