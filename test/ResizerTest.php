<?php

require_once 'Resizer.php';
require_once 'ImagePath.php';
require_once 'Configuration.php';
require_once 'Cache.php';

require_once '_helpers_test.php';

class ResizerTest extends PHPUnit_Framework_TestCase {
    private $requiredArguments = array('h' => 300, 'w' => 600);      

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNecessaryCollaboration() {
        $resizer = new Resizer('anyNonPathObject', 'nonConfigurationObject', 'nonCacheObject');
    }

    public function testInstantiation() {	
        $this->assertInstanceOf('Resizer', new Resizer(new ImagePath('',new Cache(30)), new Configuration($this->requiredArguments)));
    }
    
    public function testCreateImagePath() {
        $configuration = new Configuration($this->requiredArguments);    
        $cache = new Cache($configuration->obtainCacheMinutes());
        $resizer = new Resizer(new ImagePath(URL_IMAGE_MF, $cache),$configuration);
    }
    
    public function testCreateNewFileIsNotCache() {
        $pathNewFile = URL_IMAGE_MF;        
        $pathCacheFile = './cache/remote/mf_NewFile.jpg';        
        $configuration = new Configuration($this->requiredArguments);    
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath($pathNewFile, $cache);  
        $resizer = new Resizer($imagePath,$configuration);        
        
        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('file_exists')            
            ->willReturn(false);              
        $cache->injectFileSystem($stub);            
        
        $this->assertTrue($resizer->isNecessaryNewFile($pathCacheFile,$pathNewFile));
    }   
    
    public function testCreateNewFileCacheIsOld() {
        $pathNewFile = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';
        $pathCacheFile = './cache/remote/mf_NewFile.jpg';
        $configuration = new Configuration($this->requiredArguments);    
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath($pathNewFile, $cache);                
        $resizer = new Resizer($imagePath,$configuration);        
        
        $stub = $this->obtainMockFileExistsTrue();            
        $stub->method('filemtime')
            ->willReturn(201003101100);      
        $cache->injectFileSystem($stub);                        
        
        $this->assertTrue($resizer->isNecessaryNewFile($resizer->obtainFilePath(),$pathCacheFile));
    }    

    public function testNotCreateNewFileCacheIsMoreRecent() {        
        $pathCacheFile = './cache/remote/mf_NewFile.jpg';
        
        $configuration = new Configuration($this->requiredArguments);    
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        $resizer = new Resizer($imagePath,$configuration);                          
        
        $stub = $this->obtainMockFileCacheIsMoreRecient();        
        $cache->injectFileSystem($stub);                    
        
        $this->assertFalse($resizer->isNecessaryNewFile($resizer->obtainFilePath(),$pathCacheFile));
    }         
    
    public function testNotDoResizeFileValidExists() {               
        
        $stubConfiguration = $this->obtainMockConfiguration($this->requiredArguments);
        $stubConfiguration->method('obtainCacheMinutes')
            ->willReturn(20);                      
        
        $cache = new Cache(CACHE_MINUTES);
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);  
        $resizer = new Resizer($imagePath,$stubConfiguration);                                          
        
        $stub = $this->obtainMockFileCacheIsMoreRecient();    
        $cache->injectFileSystem($stub);                                               	                                       
        
        $this->assertEquals($resizer->doResize(),$imagePath->obtainCacheFilePath($resizer->composeNewPath()));     
    }    
    
    private function obtainMockFileExistsTrue() {
        $stubFileSystem = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stubFileSystem->method('file_exists')
            ->willReturn(true);
        $stubFileSystem->method('pathinfo')
            ->willReturn(array(
		'basename' => 'mf.jpg',
		'extension' => 'jpg'
            ));            
            
        return $stubFileSystem;
    }
    
    private function obtainMockFileCacheIsMoreRecient() {
        $timeNewFile = 20100307;
        $timeCacheFile = 20100308;

        $stubFileSystem = $this->obtainMockFileExistsTrue();    
        $stubFileSystem->method('filemtime')
            ->will($this->onConsecutiveCalls($timeNewFile,$timeNewFile,$timeCacheFile,$timeNewFile));    
            
        return $stubFileSystem;            
    }
    
    private function obtainMockConfiguration($opts) {    
        $stubConfiguration = $this->getMockBuilder('Configuration')
	  ->disableOriginalConstructor()
	  ->getMock();
	$stubConfiguration->method('obtainRemote')
            ->willReturn('./cache/remote/'); 
	$stubConfiguration->method('obtainConvertPath')
	    ->willReturn('convert');
	$stubConfiguration->method('obtainCanvasColor')
	    ->willReturn('transparent');
	$stubConfiguration->method('obtainQuality')
	    ->willReturn(90);
	$stubConfiguration->method('obtainWidth')
            ->willReturn($opts['w']);
	$stubConfiguration->method('obtainHeight')
            ->willReturn($opts['h']);            
            
        return $stubConfiguration;
    }       
}
