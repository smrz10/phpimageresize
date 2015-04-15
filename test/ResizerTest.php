<?php

require_once 'Resizer.php';
require_once 'ImagePath.php';
require_once 'Configuration.php';
require_once 'Cache.php';

date_default_timezone_set('Europe/Berlin');
define('URL_IMAGE_MF', 'http://martinfowler.com/mf.jpg?query=hello&s=fowler');
define('CACHE_MINUTES', 30);

class ResizerTest extends PHPUnit_Framework_TestCase {
    private $requiredArguments = array('h' => 300, 'w' => 600);      

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNecessaryCollaboration() {
        $resizer = new Resizer('anyNonPathObject', 'nonConfigurationObject', 'nonCache');
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
    
    public function testDefaultShellWithRequiredArguments() {  
        $configuration = new Configuration($this->requiredArguments);            
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        $resizer = new Resizer($imagePath,$configuration);    

        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                              
        
        $filePath = $resizer->obtainFilePath();
        $newPath = $resizer->composeNewPath();                
        
        $command = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -thumbnail x' . $this->requiredArguments['w'] .
                    ' -quality ' . escapeshellarg('90') . ' ' .  escapeshellarg($newPath);  
        
        $this->assertEquals($command, $resizer->defaultShellCommand());        
    }  
    
    public function testDefaultShellWithRequiredArgumentsAndMaxOnly() {
        $opts = array_merge($this->requiredArguments, array('maxOnly' => true));

        $configuration = new Configuration($opts);    
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        $resizer = new Resizer($imagePath,$configuration);    

        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                               
        
        $filePath = $resizer->obtainFilePath();
        $newPath = $resizer->composeNewPath();                
        
        $command = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -thumbnail x' . $this->requiredArguments['w'] .
                    '\> -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($command, $resizer->defaultShellCommand());        
    }    
    
    public function testDefaultShellWithMaxOnlyAndWidth() { 
        $opts = array('maxOnly' => true, 'w' => 730);

        $configuration = new Configuration($opts);            
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        $resizer = new Resizer($imagePath,$configuration);            
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                               
        
        $filePath = $resizer->obtainFilePath();
        $newPath = $resizer->composeNewPath();                        
        
        $command = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -thumbnail 730' .
                    '\> -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($command, $resizer->defaultShellCommand());        
    }   
    
    public function testDefaultShellWithoutWidth() {   
        $opts = array('h' => 730); 

        $configuration = new Configuration($opts);            
	$cache = new Cache($configuration->obtainCacheMinutes());        
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        $resizer = new Resizer($imagePath,$configuration);    
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                               
        
        $filePath = $resizer->obtainFilePath();
        $newPath = $resizer->composeNewPath();                          
        
        $command = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -thumbnail x' .
                    ' -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($command, $resizer->defaultShellCommand());        
    }    
    
    public function testDefaultShellWithMaxOnlyAndDimensionsAndQuality() {
        $opts = array('maxOnly' => true, 'h' => 2010 , 'w' => 730, 'quality' => 96);

        $configuration = new Configuration($opts);            
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        $resizer = new Resizer($imagePath,$configuration);        
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                       

        $filePath = $resizer->obtainFilePath();
        $newPath = $resizer->composeNewPath();                                 
        
        $command = 'convert ' . escapeshellarg($filePath) .
                    ' -thumbnail x730' .
                    '\> -quality ' . escapeshellarg('96') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($command, $resizer->defaultShellCommand());        
    }  
    
    public function testObtainCommandWithRequiredArguments() {	
	$stubConfiguration = $this->obtainMockConfiguration($this->requiredArguments);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(true);		            
    
	$cache = new Cache(CACHE_MINUTES);
	$imagePath = new ImagePath(URL_IMAGE_MF, $cache);            
        $resizer = new Resizer($imagePath,$stubConfiguration);  
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                       
        
        $this->assertEquals($resizer->obtainCommand(), $resizer->commandWithCrop());
    }    
    
    public function testObtainCommandDefaultWithHeight() {
	$opts = array('h' => 300, 'w' => null);
	$stubConfiguration = $this->obtainMockConfiguration($opts);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(false);	        	

	$cache = new Cache(CACHE_MINUTES);            
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        $resizer = new Resizer($imagePath,$stubConfiguration);                   
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                               
        
        $this->assertEquals($resizer->obtainCommand(), $resizer->defaultShellCommand());    
    }
    
    public function testObtainCommandDefaultWithScaleAndWidth() {
	$opts = array('h' => null, 'w' => 731);  
	$stubConfiguration = $this->obtainMockConfiguration($opts);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(false);			
        $stubConfiguration->method('obtainScale')
            ->willReturn(true);                  	
        
        $cache = new Cache(CACHE_MINUTES);            
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        $resizer = new Resizer($imagePath,$stubConfiguration);        
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                               
        
        $this->assertEquals($resizer->obtainCommand(), $resizer->defaultShellCommand());        
    }    
    
    public function testObtainCommandWithDimensionsAndNotScale() {             
	$stubConfiguration = $this->obtainMockConfiguration($this->requiredArguments);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(true);			
        $stubConfiguration->method('obtainScale')
            ->willReturn(false);                     
    
	$cache = new Cache(CACHE_MINUTES);                
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        $resizer = new Resizer($imagePath,$stubConfiguration);                           
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                                       
        
        $this->assertEquals($resizer->obtainCommand(), $resizer->commandWithCrop());        
    }
    
    public function testObtainCommandWithDimensionsAndScale() {
	$stubConfiguration = $this->obtainMockConfiguration($this->requiredArguments);
	$stubConfiguration->method('isPanoramic')
	    ->willReturn(true);			
	$stubConfiguration->method('obtainScale')
	    ->willReturn(true);          
	
	$cache = new Cache(CACHE_MINUTES);                
	$imagePath = new ImagePath(URL_IMAGE_MF, $cache);
	$resizer = new Resizer($imagePath,$stubConfiguration); 	
	
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                               	
	
	$this->assertEquals($resizer->obtainCommand(), $resizer->commandWithScale());            
    }    
    
    public function testCommandWithCropRequiredArguments() {
        $opts = $this->requiredArguments; 	
        $stubConfiguration = $this->obtainMockConfiguration($this->requiredArguments);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(false);	         
        
        $cache = new Cache(CACHE_MINUTES);                
	$imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        $resizer = new Resizer($imagePath,$stubConfiguration);                    
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                               	        
        
        $filePath = $resizer->obtainFilePath();
        $newPath = $resizer->composeNewPath();                          
        
        $command = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -resize ' . escapeshellarg('x300') .
                    ' -size ' . escapeshellarg('600x300') .
                    ' xc:' . escapeshellarg('transparent') . 
                    ' +swap -gravity center -composite' . 
                    ' -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($command, $resizer->commandWithCrop());        	
    }   
    
    public function testCommandWithCropIsPanoramicNotCrop() {
        $opts = $this->requiredArguments; 
        $stubConfiguration = $this->obtainMockConfiguration($this->requiredArguments);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(true);		         
        
        $cache = new Cache(CACHE_MINUTES);                
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);    
        $resizer = new Resizer($imagePath,$stubConfiguration);    
               
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                               	        
        
        $filePath = $resizer->obtainFilePath();
        $newPath = $resizer->composeNewPath();                          
        
        $command = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -resize ' . escapeshellarg('600') .
                    ' -size ' . escapeshellarg('600x300') .
                    ' xc:' . escapeshellarg('transparent') . 
                    ' +swap -gravity center -composite' . 
                    ' -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($command, $resizer->commandWithCrop());        	
    }    
    
    public function testCommandWithCropHasCropNotPanoramic() {
        $opts = $this->requiredArguments; 
        $stubConfiguration = $this->obtainMockConfiguration($this->requiredArguments);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(false);		
        $stubConfiguration->method('obtainCrop')
            ->willReturn(true);		         
        
        $cache = new Cache(CACHE_MINUTES);                
	$imagePath = new ImagePath(URL_IMAGE_MF, $cache);                
        $resizer = new Resizer($imagePath,$stubConfiguration);            
  
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                               	                
        
        $filePath = $resizer->obtainFilePath();
        $newPath = $resizer->composeNewPath();                 
        
        $command = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -resize ' . escapeshellarg('600') .
                    ' -size ' . escapeshellarg('600x300') .
                    ' xc:' . escapeshellarg('transparent') . 
                    ' +swap -gravity center -composite' . 
                    ' -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($command, $resizer->commandWithCrop());        	
    }    
    
    public function testCommandWithScaleRequiredArguments() {  
        $opts = $this->requiredArguments; 
        $stubConfiguration = $this->obtainMockConfiguration($this->requiredArguments);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(false);	         
        
        $cache = new Cache(CACHE_MINUTES);
	$imagePath = new ImagePath(URL_IMAGE_MF, $cache);  
        $resizer = new Resizer($imagePath,$stubConfiguration);                  
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                               	                        
        
        $filePath = $resizer->obtainFilePath();
        $newPath = $resizer->composeNewPath();                          
        
        $command = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -resize ' . escapeshellarg('x300') .
                    ' -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($command, $resizer->commandWithScale());        	
    }  
    
    public function testCommandWithScaleHasCropNotPanoramic() {
        $opts = $this->requiredArguments; 
        $stubConfiguration = $this->obtainMockConfiguration($this->requiredArguments);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(false);		
        $stubConfiguration->method('obtainCrop')
            ->willReturn(true);		         
        
        $cache = new Cache(CACHE_MINUTES);
	$imagePath = new ImagePath(URL_IMAGE_MF, $cache);  
        $resizer = new Resizer($imagePath,$stubConfiguration);                    
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                               	                        
        
        $filePath = $resizer->obtainFilePath();
        $newPath = $resizer->composeNewPath();                 
        
        $command = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -resize ' . escapeshellarg('600') .
                    ' -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($command, $resizer->commandWithScale());        	
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
