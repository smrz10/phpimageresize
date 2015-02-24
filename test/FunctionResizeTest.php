<?php

include 'Configuration.php';

class FunctionResizeTest extends PHPUnit_Framework_TestCase {

    private $defaults = array(
        'crop' => false,
        'scale' => 'false',
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
        $this->assertInstanceOf('Configuration', new Configuration);
    }

    public function testNullOptsDefaults() {
        $configuration = new Configuration(null);

        $this->assertEquals($this->defaults, $configuration->asHash());
    }

    public function testDefaults() {
        $configuration = new Configuration();
        $asHash = $configuration->asHash();

        $this->assertEquals($this->defaults, $asHash);
    }

    public function testDefaultsNotOverwriteConfiguration() {

        $opts = array(
            'thumbnail' => true,
            'maxOnly' => true
        );

        $configuration = new Configuration($opts);
        $configured = $configuration->asHash();

        $this->assertTrue($configured['thumbnail']);
        $this->assertTrue($configured['maxOnly']);
    }

    public function testObtainCache() {
        $configuration = new Configuration();

        $this->assertEquals('./cache/', $configuration->obtainCache());
    }

    public function testObtainRemote() {
        $configuration = new Configuration();

        $this->assertEquals('./cache/remote/', $configuration->obtainRemote());
    }

    public function testObtainConvertPath() {
        $configuration = new Configuration();

        $this->assertEquals('convert', $configuration->obtainConvertPath());
    }
}

?>
