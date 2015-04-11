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

    public function obtainFilePath() {
	
	return $this->path->obtainFilePath($this->configuration->obtainRemote());
    } 
        
    public function isNecessaryNewFile($newFile,$cacheFile) {	    
	
	return $this->path->existsNewPath($newFile, $cacheFile);
    }
    
    public function composeNewPath() {
	
	return $this->path->composeNewPath($this->obtainFilePath(),$this->configuration);   
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
    
    private function exec($command) {
	$exec = exec($command, $output, $return_code);

	if($return_code != 0) {
	    error_log("Tried to execute : $command, return code: $return_code, output: " . print_r($output, true));
	    throw new RuntimeException('cannot resize the image');
	}    
    }        

    public function obtainCommand() {        
	$cmd = new Command($this->obtainFilePath(), $this->composeNewPath(), $this->configuration);
	return $cmd->obtainCommand();
    }       
    
    public function defaultShellCommand() {        
	$cmd = new Command($this->obtainFilePath(), $this->composeNewPath(), $this->configuration);
	return $cmd->defaultShellCommand();
    }
    
    public function commandWithScale() {		    
	$cmd = new Command($this->obtainFilePath(), $this->composeNewPath(), $this->configuration);
	return $cmd->commandWithScale();	
    }    
    
    public function commandWithCrop() {
	$cmd = new Command($this->obtainFilePath(), $this->composeNewPath(), $this->configuration);
	return $cmd->commandWithCrop();	
    }     
    
    
    private function checkPath($path) {
        if (!($path instanceof ImagePath)) throw new InvalidArgumentException();
    }

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }     
}
