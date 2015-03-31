<?php

class ImagePath {

    private $path;
    private $valid_http_protocols = array('http', 'https');

    public function __construct($url='') {
        $this->path = $this->sanitize($url);
    }

    public function sanitizedPath() {
        return $this->path;
    }

    public function isHttpProtocol() {
        return in_array($this->obtainScheme(), $this->valid_http_protocols);
    }

    public function obtainFileName() {
        $finfo = pathinfo($this->path);
        list($filename) = explode('?',$finfo['basename']);
        return $filename;
    }
    
    public function obtainFileExtension() {
	    $fileInfo = pathinfo($this->$path);
	    return $fileInfo['extension'];        
    }

    private function sanitize($path) {
        return urldecode($path);
    }

    private function obtainScheme() {
        if ($this->path == '') return '';
        $purl = parse_url($this->path);
        return $purl['scheme'];
    }
}
