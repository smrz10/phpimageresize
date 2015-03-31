<?php

class Configuration {
    const CACHE_PATH = './cache/';
    const REMOTE_PATH = './cache/remote/';

    const CROP_KEY = 'crop';
    const SCALE_KEY = 'scale';
    const MAX_ONLY_KEY = 'maxOnly';
    const OUTPUT_FILENAME_KEY = 'output-filename';
    const CACHE_KEY = 'cacheFolder';
    const REMOTE_KEY = 'remoteFolder';
    const QUALITY_KEY = 'quality';
    const CACHE_MINUTES_KEY = 'cache_http_minutes';
    const WIDTH_KEY = 'w';
    const HEIGHT_KEY = 'h';
    

    const CONVERT_PATH = 'convert';

    private $opts;

    public function __construct($opts=array()) {
        $sanitized= $this->sanitize($opts);
        
        if ($this->haveRequiredArguments($opts) == False) {
            throw new InvalidArgumentException();
        }        

        $defaults = array(
            self::CROP_KEY => false,
            self::SCALE_KEY => false,
            'thumbnail' => false,
            self::MAX_ONLY_KEY => false,
            'canvas-color' => 'transparent',
            self::OUTPUT_FILENAME_KEY => false,
            self::CACHE_KEY => self::CACHE_PATH,
            self::REMOTE_KEY => self::REMOTE_PATH,
            self::QUALITY_KEY => 90,
            self::CACHE_MINUTES_KEY => 20,
            self::WIDTH_KEY => null,
            self::HEIGHT_KEY => null);

        $this->opts = array_merge($defaults, $sanitized);
    }

    public function asHash() {
        return $this->opts;
    }

    public function obtainCache() {
        return $this->opts[self::CACHE_KEY];
    }

    public function obtainRemote() {
        return $this->opts[self::REMOTE_KEY];
    }

    public function obtainConvertPath() {
        return self::CONVERT_PATH;
    }
    
    public function obtainCrop() {
        return $this->opts[self::CROP_KEY];
    }

    public function obtainScale() {
        return $this->opts[self::SCALE_KEY];
    }    
    
    public function obtainMaxOnly() {
        return $this->opts[self::MAX_ONLY_KEY];
    }        
    
    public function obtainQuality() {
        return $this->opts[self::QUALITY_KEY];
    }            
    
    public function obtainOutputFilename() {
        return $this->opts[self::OUTPUT_FILENAME_KEY];
    }
    
    public function obtainCacheMinutes() {
        return $this->opts[self::CACHE_MINUTES_KEY];
    }    
    
    public function obtainWidth() {
        return $this->opts[self::WIDTH_KEY];
    }

    public function obtainHeight() {
        return $this->opts[self::HEIGHT_KEY];
    }    
    
    
    public function isPanoramic($imagePath) {
	    list($width,$height) = getimagesize($imagePath);
	    return $width > $height;
    }    
    
    

    private function haveRequiredArguments($opts) {
        $empty_filename = empty($opts[self::OUTPUT_FILENAME_KEY]);
        $empty_width = empty($opts[self::WIDTH_KEY]);
        $empty_height = empty($opts[self::HEIGHT_KEY]);
    
        $haveRequiredArguments = True;
        if ($empty_filename && $empty_width && $empty_height) {
            $haveRequiredArguments = False;
        }
        
        return $haveRequiredArguments;
    }
    
    private function sanitize($opts) {
        if($opts == null) return array();

        return $opts;
    }

}
