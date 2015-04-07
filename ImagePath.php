<?php

class ImagePath {

    private $path;
    private $fileSystem;
    
    private $valid_http_protocols = array('http', 'https');

    public function __construct($url='') {
        $this->path = $this->sanitize($url);
	$this->fileSystem = new FileSystem();
    }

    public function injectFileSystem(FileSystem $fileSystem) {
        $this->fileSystem = $fileSystem;
    }
    
    
    public function sanitizedPath() {
        
        return $this->path;
    }

    public function isHttpProtocol() {
        
        return in_array($this->obtainScheme(), $this->valid_http_protocols);
    }

    public function obtainFileName() {
        $finfo = $this->fileSystem->pathinfo($this->path);
        list($filename) = explode('?',$finfo['basename']);
        
        return $filename;
    }
    
    public function obtainFileExtension() {
	$fileInfo = $this->fileSystem->pathinfo($this->path);
	
	return $fileInfo['extension'];        
    }
    
    public function isFileExternal() {
    
	return $this->isHttpProtocol();
    }
    
    public function obtainFilePathLocal($cacheRemotePath) {
	$filename = $this->obtainFileName();
	$local_filepath = $cacheRemotePath .$filename;	

	return $local_filepath;
    }
    
    //////////////////
    public function composeNewPath($filePath, $configuration) {
	$filename = md5_file($filePath);
	$widthSignal = $this->obtainSignalWidth($configuration);
	$heightSignal = $this->obtainSignalHeight($configuration);
	$cropSignal = $this->obtainSignalCrop($configuration);
	$scaleSignal = $this->obtainSignalScale($configuration);
	$extension = '.' . $this->obtainFileExtension();

	$newPath = $configuration->obtainCache().$filename.$widthSignal.$heightSignal.$cropSignal.$scaleSignal.$extension;

        $outputFilename = $configuration->obtainOutputFilename();
        if ($outputFilename) {        
	    $newPath = $outputFilename;
	}

	return $newPath;               
    }   
    
    private function obtainSignalCrop($configuration) {
        $signalCrop = "";
        if ($configuration->obtainCrop() == true) {            
            $signalCrop = "_cp";
        }
        
        return $signalCrop;
    }

    private function obtainSignalScale($configuration) {
        $signalScale = "";
        if ($configuration->obtainScale() == true) {            
            $signalScale = "_sc";
        }
        
        return $signalScale;
    }        
    
    private function obtainSignalHeight($configuration) {
        $signalHeight = "";
	$height = $configuration->obtainHeight();        
        
        if (!empty($height)) {            
            $signalHeight = "_h".$height;
        }
        
        return $signalHeight;
    }    

    private function obtainSignalWidth($configuration) {
        $signalWidth = "";
	$width = $configuration->obtainWidth();        
        
        if (!empty($width)) {            
            $signalWidth = "_w".$width;
        }
        
        return $signalWidth;
    }    

    ////////
    
    private function sanitize($path) {
        
        return urldecode($path);
    }

    private function obtainScheme() {
        if ($this->path == '') return '';
        $purl = parse_url($this->path);
        
        return $purl['scheme'];
    }
}
