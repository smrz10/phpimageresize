<?php

include 'Configuration.php';

class FunctionResizeTest extends PHPUnit_Framework_TestCase {
    private $RequiredArguments = array('output-filename' => 'test', 'h' => 300, 'w' => 600);    

    private $defaults = array(
        'crop' => false,
        'scale' => false,
        'thumbnail' => false,
        'maxOnly' => false,
        'canvas-color' => 'transparent',
        'output-filename' => false,
        'cacheFolder' => './cache/',
        'remoteFolder' => './cache/remote/',
        'quality' => 90,
        'cache_http_minutes' => 20,
        'w' => null,
        'h' => null
    );

    public function testOpts()
    {
        $this->assertInstanceOf('Configuration', new Configuration($this->RequiredArguments));
    }

    /**
     * @expectedException InvalidArgumentException
     */    
    public function testNullOpts() {
        $configuration = new Configuration(null);
    }     
    
    /**
     * @expectedException InvalidArgumentException
     */    
    public function testEmptyOpts() {
        $configuration = new Configuration();
    }         
     
    public function testOneRequiredArguments() {
        $onlyFilename = array('output-filename' => 'testFileName');
        $configuration = new Configuration($onlyFilename);        
        $asHash = $configuration->asHash();
        $this->assertEquals($asHash['output-filename'],$onlyFilename['output-filename']);        
        
        $onlyWidth = array('w' => 7310);
        $configuration = new Configuration($onlyWidth);        
        $asHash = $configuration->asHash();
        $this->assertEquals($asHash['w'],$onlyWidth['w']);                
        
        $onlyHeight = array('h' => 730);
        $configuration = new Configuration($onlyHeight);        
        $asHash = $configuration->asHash();
        $this->assertEquals($asHash['h'],$onlyHeight['h']);                        
    } 
     
    /**
     * @expectedException InvalidArgumentException
     */    
    public function testNullOptsArgument() {
        $configuration = new Configuration(null);
    }

    public function testDefaultsMergeRequiredArguments() {
        $configuration = new Configuration($this->RequiredArguments);
        $asHash = $configuration->asHash();
        $DefaultsMergeRequiredArguments = array_merge($this->defaults,$this->RequiredArguments);

        $this->assertEquals($DefaultsMergeRequiredArguments, $asHash);
    }

    public function testDefaultsNotOverwriteConfiguration() {
        $opts = array(
            'thumbnail' => true,
            'maxOnly' => true
        );
        
        $opts = array_merge($opts, $this->RequiredArguments);

        $configuration = new Configuration($opts);
        $configured = $configuration->asHash();

        $this->assertTrue($configured['thumbnail']);
        $this->assertTrue($configured['maxOnly']);
        $this->assertEquals($this->RequiredArguments['h'],$configured['h']);
        $this->assertEquals($this->RequiredArguments['w'],$configured['w']);     
    }

    public function testObtainCache() {

        $configuration = new Configuration($this->RequiredArguments);

        $this->assertEquals('./cache/', $configuration->obtainCache());
    }

    public function testObtainRemote() {
        $configuration = new Configuration($this->RequiredArguments);

        $this->assertEquals('./cache/remote/', $configuration->obtainRemote());
    }

    public function testObtainConvertPath() {
        $configuration = new Configuration($this->RequiredArguments);

        $this->assertEquals('convert', $configuration->obtainConvertPath());
    }
}

?>
