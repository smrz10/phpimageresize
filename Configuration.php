<?php

class Configuration {
    const CACHE_PATH = './cache/';
    const REMOTE_PATH = './cache/remote/';

    const OUTPUT_FILENAME_KEY = 'output-filename';
    const CACHE_KEY = 'cacheFolder';
    const REMOTE_KEY = 'remoteFolder';
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
            'crop' => false,
            'scale' => 'false',
            'thumbnail' => false,
            'maxOnly' => false,
            'canvas-color' => 'transparent',
            self::OUTPUT_FILENAME_KEY => false,
            self::CACHE_KEY => self::CACHE_PATH,
            self::REMOTE_KEY => self::REMOTE_PATH,
            'quality' => 90,
            'cache_http_minutes' => 20,
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

    public function obtainWidth() {
        return $this->opts[self::WIDTH_KEY];
    }

    public function obtainHeight() {
        return $this->opts[self::HEIGHT_KEY];
    }

    public function obtainCacheMinutes() {
        return $this->opts[self::CACHE_MINUTES_KEY];
    }
    
    // Y SI ES EL RESIZER EL QUE DEBE COMPROBAR Y NO ACTUAR
    // CUANDO LA CONFIGURACIÓN NO ES SUFICIENTE PARA HACER UN RESIZE ????????
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
