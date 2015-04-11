<?php

class Cache {

    private $cacheMinutes;    
    private $fileSystem;
    

    public function __construct($cacheMinutes) {
	$this->cacheMinutes = $cacheMinutes;
	$this->fileSystem = new FileSystem();
    }

    public function injectFileSystem(FileSystem $fileSystem) {
        $this->fileSystem = $fileSystem;
    }
    
      
    public function isInCache($filePath) {
        $fileExists = $this->fileSystem->file_exists($filePath);
        if ($fileExists == True) {
            $fileValid = $this->fileNotExpired($filePath);
        }

        return $fileExists && $fileValid;
    }  
    
    public function checkFileInLocal($filePath) {
        if(!$this->fileSystem->file_exists($filePath)) {
            $filePath = $_SERVER['DOCUMENT_ROOT'].$filePath;
            if(!$this->fileSystem->file_exists($filePath)) {                
		return false;                
            }
        }  
        
        return true;
    }

    public function download($imagePath, $local_filePath) {
        $img = $this->fileSystem->file_get_contents($imagePath);
        $this->fileSystem->file_put_contents($local_filePath,$img);
    }     
    
    public function isNecessaryNewFile($newFile,$cacheFile) {	    
	$fileExists = $this->isInCache($newFile);
	if ($fileExists == true) {
	    $isCacheMoreRecent = $this->isCacheMoreRecent($newFile,$cacheFile);		    
	}
	$isNecessaryNewFile = !($fileExists && $isCacheMoreRecent);

	return $isNecessaryNewFile;        
    }
    
    private function isCacheMoreRecent($newFile,$cacheFile) {	        
	$cacheFileTime = date("YmdHis",$this->fileSystem->filemtime($cacheFile));
	$newFileTime = date("YmdHis",$this->fileSystem->filemtime($newFile));       
	$isCacheMoreRecent = $newFileTime < $cacheFileTime;

	return $isCacheMoreRecent;
    }    
    
    private function fileNotExpired($filePath) {
        $cacheMinutes = $this->cacheMinutes;
        $fileNotExpired = $this->fileSystem->filemtime($filePath) < strtotime('+'. $cacheMinutes. ' minutes');
        
        return $fileNotExpired;
    }         
}