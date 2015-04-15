<?php

require_once '_helpers_test.php';

class CommandTest extends PHPUnit_Framework_TestCase {
    private $requiredArguments = array('h' => 300, 'w' => 600); 
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testNecessaryCollaboration() {
        $resizer = new Command('anyNonPathObject', 'anyNonPathObject', 'nonConfigurationObject');
    }

    public function testInstantiation() {	
	$imagePath = './cache/remote/image_original';
	$newPath = './cache/image_resize';
        $this->assertInstanceOf('Command', new Command($imagePath,$newPath, new configuration($this->requiredArguments)));
    }    

    public function testDefaultShellWithRequiredArguments() {  
        $configuration = new Configuration($this->requiredArguments);            
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);                

        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                      
        
        $filePath = $imagePath->obtainFilePath($configuration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$configuration);                
        $command = new Command($filePath, $newPath, $configuration);
        
        $cmd = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -thumbnail x' . $this->requiredArguments['w'] .
                    ' -quality ' . escapeshellarg('90') . ' ' .  escapeshellarg($newPath);  
        
        $this->assertEquals($cmd, $command->defaultShellCommand());        
    }  
    
    public function testDefaultShellWithRequiredArgumentsAndMaxOnly() {
        $opts = array_merge($this->requiredArguments, array('maxOnly' => true));

        $configuration = new Configuration($opts);    
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);

        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                               
        
        $filePath = $imagePath->obtainFilePath($configuration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$configuration);                
        $command = new Command($filePath, $newPath, $configuration);
        
        $cmd = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -thumbnail x' . $this->requiredArguments['w'] .
                    '\> -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($cmd, $command->defaultShellCommand());        
    }    
    
    public function testDefaultShellWithMaxOnlyAndWidth() { 
        $opts = array('maxOnly' => true, 'w' => 730);

        $configuration = new Configuration($opts);            
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                               
        
        $filePath = $imagePath->obtainFilePath($configuration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$configuration);                
        $command = new Command($filePath, $newPath, $configuration);
        
        $cmd = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -thumbnail 730' .
                    '\> -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($cmd, $command->defaultShellCommand());        
    }   
    
    public function testDefaultShellWithoutWidth() {   
        $opts = array('h' => 730); 

        $configuration = new Configuration($opts);            
	$cache = new Cache($configuration->obtainCacheMinutes());        
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                               
        
        $filePath = $imagePath->obtainFilePath($configuration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$configuration);                
        $command = new Command($filePath, $newPath, $configuration);
        
        $cmd = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -thumbnail x' .
                    ' -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($cmd, $command->defaultShellCommand());        
    }    
    
    public function testDefaultShellWithMaxOnlyAndDimensionsAndQuality() {
        $opts = array('maxOnly' => true, 'h' => 2010 , 'w' => 730, 'quality' => 96);

        $configuration = new Configuration($opts);            
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                       

        $filePath = $imagePath->obtainFilePath($configuration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$configuration);                
        $command = new Command($filePath, $newPath, $configuration);                      
        
        $cmd = 'convert ' . escapeshellarg($filePath) .
                    ' -thumbnail x730' .
                    '\> -quality ' . escapeshellarg('96') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($cmd, $command->defaultShellCommand());        
    }  
    
    public function testObtainCommandWithRequiredArguments() {	
	$stubConfiguration = $this->obtainMockConfiguration($this->requiredArguments);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(true);		            
    
	$cache = new Cache(CACHE_MINUTES);
	$imagePath = new ImagePath(URL_IMAGE_MF, $cache);            
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                             
        
        $filePath = $imagePath->obtainFilePath($stubConfiguration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$stubConfiguration);                
        $command = new Command($filePath, $newPath, $stubConfiguration);        
        
        $this->assertEquals($command->obtainCommand(), $command->commandWithCrop());
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
        
        $filePath = $imagePath->obtainFilePath($stubConfiguration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$stubConfiguration);                
        $command = new Command($filePath, $newPath, $stubConfiguration);                
        
        $this->assertEquals($command->obtainCommand(), $command->defaultShellCommand());    
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
        
        $filePath = $imagePath->obtainFilePath($stubConfiguration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$stubConfiguration);                
        $command = new Command($filePath, $newPath, $stubConfiguration);                
        
        $this->assertEquals($command->obtainCommand(), $command->defaultShellCommand());        
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

        $filePath = $imagePath->obtainFilePath($stubConfiguration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$stubConfiguration);                
        $command = new Command($filePath, $newPath, $stubConfiguration);                
        
        $this->assertEquals($command->obtainCommand(), $command->commandWithCrop());        
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
        
        $filePath = $imagePath->obtainFilePath($stubConfiguration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$stubConfiguration);                
        $command = new Command($filePath, $newPath, $stubConfiguration);                
	
	$this->assertEquals($command->obtainCommand(), $command->commandWithScale());            
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
        
        $filePath = $imagePath->obtainFilePath($stubConfiguration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$stubConfiguration);                
        $command = new Command($filePath, $newPath, $stubConfiguration);        
        
        $cmd = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -resize ' . escapeshellarg('x300') .
                    ' -size ' . escapeshellarg('600x300') .
                    ' xc:' . escapeshellarg('transparent') . 
                    ' +swap -gravity center -composite' . 
                    ' -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($cmd, $command->commandWithCrop());        	
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
        
        $filePath = $imagePath->obtainFilePath($stubConfiguration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$stubConfiguration);                
        $command = new Command($filePath, $newPath, $stubConfiguration);        
        
        $cmd = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -resize ' . escapeshellarg('600') .
                    ' -size ' . escapeshellarg('600x300') .
                    ' xc:' . escapeshellarg('transparent') . 
                    ' +swap -gravity center -composite' . 
                    ' -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($cmd, $command->commandWithCrop());        	
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
        
        $filePath = $imagePath->obtainFilePath($stubConfiguration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$stubConfiguration);                
        $command = new Command($filePath, $newPath, $stubConfiguration);        
        
        $cmd = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -resize ' . escapeshellarg('600') .
                    ' -size ' . escapeshellarg('600x300') .
                    ' xc:' . escapeshellarg('transparent') . 
                    ' +swap -gravity center -composite' . 
                    ' -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($cmd, $command->commandWithCrop());        	
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
        
        $filePath = $imagePath->obtainFilePath($stubConfiguration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$stubConfiguration);                
        $command = new Command($filePath, $newPath, $stubConfiguration);        
        
        $cmd = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -resize ' . escapeshellarg('x300') .
                    ' -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($cmd, $command->commandWithScale());        	
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
        
        $filePath = $imagePath->obtainFilePath($stubConfiguration->obtainRemote());        
        $newPath = $imagePath->composeNewPath($filePath,$stubConfiguration);                
        $command = new Command($filePath, $newPath, $stubConfiguration);        
        
        $cmd = 'convert ' . escapeshellarg(urldecode($filePath)) .
                    ' -resize ' . escapeshellarg('600') .
                    ' -quality ' . escapeshellarg('90') . ' ' . escapeshellarg($newPath);  
        
        $this->assertEquals($cmd, $command->commandWithScale());        	
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
