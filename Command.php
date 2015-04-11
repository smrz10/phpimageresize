<?php

class Command {

    private $configuration;
    private $imagePath;
    private $newPath;
    private $height;
    private $width;

    public function __construct($imagePath, $newPath, $configuration) {    
        $this->checkConfiguration($configuration);	
	
	$this->imagePath = escapeshellarg($imagePath);
	$this->newPath = escapeshellarg($newPath);
	$this->configuration = $configuration;                        

	$this->height = $configuration->obtainHeight();
	$this->width = $configuration->obtainWidth();
    }

    public function obtainCommand() {        
	$commandWithDimensions = (!empty($this->width) && !empty($this->height));        
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

        $command = $this->configuration->obtainConvertPath() . " " . $this->imagePath . " ".
                    $this->obtainCmdArgumentThumbnail() .
                    $this->obtainCmdArgumentMaxOnly() . " " .
                    $this->obtainCmdArgumentQuality() . " " . $this->newPath;                   

	return $command;
    }
    
    public function commandWithScale() {		    
		    
	$command = $this->configuration->obtainConvertPath() . " " . $this->imagePath . " " .
		    $this->obtainCmdArgumentResize() . " " .
		    $this->obtainCmdArgumentQuality() . " " . $this->newPath;                   

	return $command;
    }    
    
    public function commandWithCrop() {

        $command = $this->configuration->obtainConvertPath() . " " . $this->imagePath . " " .
                    $this->obtainCmdArgumentResize() . " " .
                    $this->obtainCmdArgumentSize() . " " .
                    $this->obtainCmdArgumentCanvasColor() . " " .
                    $this->obtainCmdArgumentsForCrop() . " " .
                    $this->obtainCmdArgumentQuality() . " " . $this->newPath;                  

	return $command;
    }   
    
    private function obtainCmdArgumentThumbnail() {
	$separator = "";      	
	if (!empty($this->height)) {
	    $separator = "x";       
	}	    
	$argumentCommand = "-thumbnail ". $separator . $this->width;
               
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
	$resize = escapeshellarg($this->composeResizeOptions($this->imagePath));  
	$argumentCommand = "-resize ". $resize; 
	
	return $argumentCommand;
    }
    
    private function composeResizeOptions() {
	$hasCrop = ($this->configuration->obtainCrop() == true);
	$isPanoramic = $this->configuration->isPanoramic($this->imagePath);

        $isPanoramicWithoutCrop = $isPanoramic && !$hasCrop;
        $NotIsPanoramicWithCrop = !$isPanoramic && $hasCrop; 

        $resize = "x".$this->height;

	if($isPanoramicWithoutCrop) {
		$resize = $this->width;
	}

	if($NotIsPanoramicWithCrop) {
		$resize = $this->width;
	}

	return $resize;
    }     
    
    private function obtainCmdArgumentSize() {
      	$size = escapeshellarg($this->width ."x". $this->height);
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

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }
}