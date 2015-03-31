<?php

require 'FileSystem.php';

class Resizer {

    private $path;
    private $configuration;
    private $fileSystem;

    public function __construct($path, $configuration) {
        $this->checkPath($path);
        $this->checkConfiguration($configuration);
        $this->path = $path;
        $this->configuration = $configuration;
        $this->fileSystem = new FileSystem();
    }

    public function injectFileSystem(FileSystem $fileSystem) {
        $this->fileSystem = $fileSystem;
    }

    public function obtainFilePath() {
        $imagePath = '';

        if($this->path->isHttpProtocol()):
            $filename = $this->path->obtainFileName();
            $local_filepath = $this->configuration->obtainRemote() .$filename;
            $inCache = $this->isInCache($local_filepath);

            if(!$inCache):
                $this->download($local_filepath);
            endif;
            $imagePath = $local_filepath;
        endif;

        if(!$this->fileSystem->file_exists($imagePath)):
            $imagePath = $_SERVER['DOCUMENT_ROOT'].$imagePath;
            if(!$this->fileSystem->file_exists($imagePath)):
                throw new RuntimeException();
            endif;
        endif;

        return $imagePath;
    }


    private function download($filePath) {
        $img = $this->fileSystem->file_get_contents($this->path->sanitizedPath());
        $this->fileSystem->file_put_contents($filePath,$img);
    }

    private function isInCache($filePath) {
        $fileExists = $this->fileSystem->file_exists($filePath);
        if ($fileExists == True) {
            $fileValid = $this->fileNotExpired($filePath);
        }

        return $fileExists && $fileValid;
    }

    private function fileNotExpired($filePath) {
        $cacheMinutes = $this->configuration->obtainCacheMinutes();
        $fileNotExpired = $this->fileSystem->filemtime($filePath) < strtotime('+'. $cacheMinutes. ' minutes');
        
        return $fileNotExpired;
    }

    private function checkPath($path) {
        if (!($path instanceof ImagePath)) throw new InvalidArgumentException();
    }

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }    
        
    public function isNecessaryNewFile($newFile,$cacheFile) {	    
	    $fileExists = $this->isInCache($newFile);
	    if ($fileExists == True) {
	        $isCacheMoreRecent = $this->isCacheMoreRecent($newFile,$cacheFile);		    
	    }
	    $isNecessaryNewFile = !($fileExists && $isCacheMoreRecent);

	    return $isNecessaryNewFile;        
    }
    
    private function isCacheMoreRecent($newFile,$cacheFile) {	        
	    $cacheFileTime = date("YmdHis",$this->fileSystem->filemtime($cacheFile));
	    $newFileTime = date("YmdHis",$this->fileSystem->filemtime($newFile));       
	    $isCacheMoreRecent = $newFileTime > $cacheFileTime;
	    
	    return $isCacheMoreRecent;
    }
    
    public function composeNewPath() {
	    $filename = md5_file($this->obtainFilePath());
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
 

}
