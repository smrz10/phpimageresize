<?php

require 'ComposePath.php';

class ImagePath {

    private $path;
    private $cache;
    
    private $valid_http_protocols = array('http', 'https');

    public function __construct($url='', $cache) {
        $this->checkCache($cache);
        
        $this->path = $this->sanitize($url);
	$this->cache = $cache;
    }    
    
    public function sanitizedPath() {
        
        return $this->path;
    }

    public function isHttpProtocol() {
        
        return in_array($this->obtainScheme(), $this->valid_http_protocols);
    }

    public function obtainFilePath($remote) {
        $imagePath = '';

        if($this->isFileExternal()):
	    $cacheRemotePath = $remote;
	    $local_filepath = $this->obtainFilePathLocal($cacheRemotePath);
            $inCache = $this->cache->isInCache($local_filepath);

            if(!$inCache):
                $this->cache->download($this->sanitizedPath(), $local_filepath);
            endif;
            $imagePath = $local_filepath;
        endif;

	if (!$this->cache->checkFileInLocal($imagePath)) {	
	    throw new RuntimeException('image not found');
	}

        return $imagePath;
    }     
    
    public function obtainFileName() {        
        $finfo = $this->cache->pathinfo($this->path);
        list($filename) = explode('?',$finfo['basename']);
        
        return $filename;
    }
    
    public function obtainFileExtension() {	
	$fileInfo = $this->cache->pathinfo($this->path);
	
	return $fileInfo['extension'];        
    }  
    
    public function obtainFilePathLocal($cacheRemotePath) {
	$filename = $this->obtainFileName();
	$local_filepath = $cacheRemotePath .$filename;	

	return $local_filepath;
    } 
    
    public function obtainCacheFilePath($path) {
        $newPath = escapeshellarg($path);
        $filePathRelative = str_replace($_SERVER['DOCUMENT_ROOT'],'',$newPath);       
        
        return $filePathRelative;
    }    
    
    public function composeNewPath($filePath, $configuration) {
	$composer = new ComposePath($filePath, $this->obtainFileExtension(), $configuration);
	return $composer->composeNewPath();   
    }   
    
    public function existsNewPath($newFile, $cacheFile) {
	return $this->cache->isNecessaryNewFile($newFile,$cacheFile);    
    }    

    private function sanitize($path) {
         
        return urldecode($path);
    }
    
    private function isFileExternal() {
    
	return $this->isHttpProtocol();
    }    
 
    private function obtainScheme() {
        if ($this->path == '') return '';
        $purl = parse_url($this->path);
        
        return $purl['scheme'];
    }
    
    private function checkCache($cache) {
        if (!($cache instanceof Cache)) throw new InvalidArgumentException();
    }    
}
