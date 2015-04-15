<?php

require 'Command.php';

class Resizer {

    private $path;
    private $configuration; 
    
    public function __construct($path, $configuration) {    
        $this->checkPath($path);
        $this->checkConfiguration($configuration);
        
        $this->path = $path;
        $this->configuration = $configuration;                
    }
 
    public function doResize() {    	
        $imagePath = $this->obtainFilePath();
        $newPath = $this->composeNewPath(); 
        
        if ($this->isNecessaryNewFile($imagePath,$newPath) == false) {
            return $this->path->obtainCacheFilePath($newPath);
        }    
	
	$cmd = new Command($imagePath, $newPath, $this->configuration);        
	$this->exec($cmd ->obtainCommand());
	
	return $this->path->obtainCacheFilePath($newPath);
    }
    
    public function obtainFilePath() {
	
	return $this->path->obtainFilePath($this->configuration->obtainRemote());
    } 
        
    public function isNecessaryNewFile($newFile,$cacheFile) {	    
	
	return $this->path->existsNewPath($newFile, $cacheFile);
    }      
    
    private function composeNewPath() {
	
	return $this->path->composeNewPath($this->obtainFilePath(),$this->configuration);   
    }         
    
    private function exec($command) {
	$exec = exec($command, $output, $return_code);

	if($return_code != 0) {
	    error_log("Tried to execute : $command, return code: $return_code, output: " . print_r($output, true));
	    throw new RuntimeException('cannot resize the image');
	}    
    }          
    
    private function checkPath($path) {
        if (!($path instanceof ImagePath)) throw new InvalidArgumentException();
    }

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }     
}
