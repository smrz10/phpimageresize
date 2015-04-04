<?php

require_once 'Resizer.php';
require_once 'ImagePath.php';
require_once 'Configuration.php';
date_default_timezone_set('Europe/Berlin');


class ResizerTest extends PHPUnit_Framework_TestCase {
    private $requiredArguments = array('h' => 300, 'w' => 600); 

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNecessaryCollaboration() {
        $resizer = new Resizer('anyNonPathObject', 'nonConfigurationObject');
    }

    public function testInstantiation() {
        $this->assertInstanceOf('Resizer', new Resizer(new ImagePath(''), new Configuration($this->requiredArguments)));
    }
    
    public function testTryCatch() {
        $ExceptionCatch = false;
    
        try {
            $this->testObtainFilePathErrorNotFile();
        } catch (Exception $e) {
            $ExceptionCatch = true;
        }   
        
        $this->assertTrue($ExceptionCatch);  
    }    

    /**
     * @expectedException RuntimeException
     */
    public function testObtainFilePathErrorNotFile() {
        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('file_exists')
            ->willReturn(false);              

        $configuration = new Configuration($this->requiredArguments);
        $imagePath = new ImagePath('');        
        $resizer = new Resizer($imagePath, $configuration);          
        $resizer->obtainFilePath();            
    }

    public function testObtainLocallyCachedFilePath() {
        $configuration = new Configuration(array('w' => 800, 'h' => 600));
        $imagePath = new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler');
        $resizer = new Resizer($imagePath, $configuration);

        $stub = $this->obtainMockFileExistsTrue();
        $stub->method('file_get_contents')
            ->willReturn('foo');

        $resizer->injectFileSystem($stub);

        $this->assertEquals('./cache/remote/mf.jpg', $resizer->obtainFilePath());

    }

    public function testLocallyCachedFilePathFail() {
        $stub = $this->obtainMockFileExistsTrue();
        $stub->method('filemtime')
            ->willReturn(21 * 60);

        $configuration = new Configuration(array('w' => 800, 'h' => 600));
        $imagePath = new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler');
        $resizer = new Resizer($imagePath, $configuration);
        $resizer->injectFileSystem($stub);

        $this->assertEquals('./cache/remote/mf.jpg', $resizer->obtainFilePath());

    }

    public function testCreateNewPath() {
        $configuration = new Configuration($this->requiredArguments);    
        $resizer = new Resizer(new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler'),$configuration );
    }
    
    public function testCreateNewFileIsNotCache() {
        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('file_exists')            
            ->willReturn(false);

        $pathNewFile = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';        
        $pathCacheFile = './cache/remote/mf_NewFile.jpg';
        $pathNewFile = './cache/remote/mf_NewFile.jpg';
        $configuration = new Configuration($this->requiredArguments);    
        $imagePath = new ImagePath($pathNewFile);       
        $resizer = new Resizer($imagePath,$configuration );        
        $resizer->injectFileSystem($stub);
        
        $this->assertTrue($resizer->isNecessaryNewFile($pathCacheFile,$pathNewFile));
    }   
    
    public function testCreateNewFileCacheIsOld() {
        $stub = $this->obtainMockFileExistsTrue();            
        $stub->method('filemtime')
            ->willReturn(201003101100);      

        $pathNewFile = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';
        $pathCacheFile = './cache/remote/mf_NewFile.jpg';
        $configuration = new Configuration($this->requiredArguments);    
        $imagePath = new ImagePath($pathNewFile);                
        $resizer = new Resizer($imagePath,$configuration );        
        $resizer->injectFileSystem($stub);
        
        $this->assertTrue($resizer->isNecessaryNewFile($resizer->obtainFilePath(),$pathCacheFile));
    }    

    public function testNotCreateNewFileCacheIsMoreRecent() {
        $pathFile = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';
        $pathCacheFile = './cache/remote/mf_NewFile.jpg';
        
        $configuration = new Configuration($this->requiredArguments);    
        $imagePath = new ImagePath($pathFile);
        $resizer = new Resizer($imagePath,$configuration );                          
        $resizer->injectFileSystem($this->obtainMockFileCacheIsMoreRecient());
        
        $this->assertFalse($resizer->isNecessaryNewFile($resizer->obtainFilePath(),$pathCacheFile));
    }         

    public function testComposeNewPathRequerisArguments() {            
        $configuration = new Configuration($this->requiredArguments);            
        $resizer = new Resizer($this->obtainMockImagePath(),$configuration);
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());    
                       
        $height = $this->requiredArguments['h'];
        $width = $this->requiredArguments['w'];        
        $newPath = $configuration->obtainCache().md5_file($resizer->obtainFilePath()).'_w'.$width.'_h'.$height.'.jpg';                                    
        
        $this->assertEquals($newPath, $resizer->composeNewPath());
    }
    
    public function testComposeNewPathOnlyOutputFilename() {
        $newPath = 'mf_e_dio';
 
        $configuration = new Configuration(array('output-filename'=>$newPath));            
        $resizer = new Resizer($this->obtainMockImagePath(),$configuration);
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());                                        
                
        
        $this->assertEquals($newPath, $resizer->composeNewPath());        
    }    
    
    public function testComposeNewPathWithScaleAndWidth() { 
        $opts = array('scale'=>true, 'w'=>125);
        
        $configuration = new Configuration($opts);            
        $resizer = new Resizer($this->obtainMockImagePath(),$configuration);
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());                           

        $width = $opts['w'];        
        $newPath = $configuration->obtainCache().md5_file($resizer->obtainFilePath()).'_w'.$width.'_sc'.'.jpg';                                    
        
        $this->assertEquals($newPath, $resizer->composeNewPath());   
    }
    
    public function testComposeNewPathWithCropAndHeight() {     
        $opts = array('crop'=>true, 'h'=>325);
        
        $configuration = new Configuration($opts);            
        $resizer = new Resizer($this->obtainMockImagePath(),$configuration);
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());                           

        $height = $opts['h'];        
        $newPath = $configuration->obtainCache().md5_file($resizer->obtainFilePath()).'_h'.$height.'_cp'.'.jpg';                                    
        
        $this->assertEquals($newPath, $resizer->composeNewPath());       
    }        

    public function testComposeNewPathWithCropAndScaleAndHeight() {      
        $opts = array('crop'=>true, 'scale'=>true, 'h'=>7310);
        
        $configuration = new Configuration($opts);            
        $resizer = new Resizer($this->obtainMockImagePath(),$configuration);
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());                           

        $height = $opts['h'];        
        $newPath = $configuration->obtainCache().md5_file($resizer->obtainFilePath()).'_h'.$height.'_cp_sc'.'.jpg';                                    
        
        $this->assertEquals($newPath, $resizer->composeNewPath());           
    }            

    public function testComposeNewPathWithCropAndOutputFilename() {
        $newPath = 'mf_e_dio';
        $width = 300;     

        $configuration = new Configuration(array('crop'=>true, 'w'=>$width, 'output-filename'=>$newPath));            
        $resizer = new Resizer($this->obtainMockImagePath(),$configuration);
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());                           
        
        $this->assertEquals($newPath, $resizer->composeNewPath());                   
    }  
    
    public function testDefaultShellWithRequiredArguments() {  

        $configuration = new Configuration($this->requiredArguments);            
        $resizer = new Resizer($this->obtainMockImagePath(),$configuration);    
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());         
        
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
        $resizer = new Resizer($this->obtainMockImagePath(),$configuration);    
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());         
        
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
        $resizer = new Resizer($this->obtainMockImagePath(),$configuration);    
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());         
        
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
        $resizer = new Resizer($this->obtainMockImagePath(),$configuration);    
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());         
        
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
        $resizer = new Resizer($this->obtainMockImagePath(),$configuration);           
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());                 

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
            
        $resizer = new Resizer($this->obtainMockImagePath(),$stubConfiguration);           
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());
        
        $this->assertEquals($resizer->obtainCommand(), $resizer->commandWithCrop());
    }    
    
    public function testObtainCommandDefaultWithHeight() {
	$opts = array('h' => 300, 'w' => null);
	$stubConfiguration = $this->obtainMockConfiguration($opts);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(false);			
            
        $resizer = new Resizer($this->obtainMockImagePath(),$stubConfiguration);           
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());
        
        $this->assertEquals($resizer->obtainCommand(), $resizer->defaultShellCommand());    
    }
    
    public function testObtainCommandDefaultWithScaleAndWidth() {
	$opts = array('h' => null, 'w' => 731);
	$stubConfiguration = $this->obtainMockConfiguration($opts);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(false);			
        $stubConfiguration->method('obtainScale')
            ->willReturn(true);            
            
        $resizer = new Resizer($this->obtainMockImagePath(),$stubConfiguration);           
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());
        
        $this->assertEquals($resizer->obtainCommand(), $resizer->defaultShellCommand());        
    }    
    
    public function testObtainCommandWithDimensionsAndNotScale() {
	$stubConfiguration = $this->obtainMockConfiguration($this->requiredArguments);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(true);			
        $stubConfiguration->method('obtainScale')
            ->willReturn(false);                        
            
        $resizer = new Resizer($this->obtainMockImagePath(),$stubConfiguration);           
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());
        
        $this->assertEquals($resizer->obtainCommand(), $resizer->commandWithCrop());        
    }
    
    public function testObtainCommandWithDimensionsAndScale() {
	$stubConfiguration = $this->obtainMockConfiguration($this->requiredArguments);
	$stubConfiguration->method('isPanoramic')
	    ->willReturn(true);			
	$stubConfiguration->method('obtainScale')
	    ->willReturn(true);                        
	    
	$resizer = new Resizer($this->obtainMockImagePath(),$stubConfiguration);           
	$resizer->injectFileSystem($this->obtainMockFileExistsTrue());
	
	$this->assertEquals($resizer->obtainCommand(), $resizer->commandWithScale());            
    }    
    
    public function testCommandWithCropRequiredArguments() {
        $opts = $this->requiredArguments; 

        $stubConfiguration = $this->obtainMockConfiguration($this->requiredArguments);
        $stubConfiguration->method('isPanoramic')
            ->willReturn(false);		
        $resizer = new Resizer($this->obtainMockImagePath(),$stubConfiguration);    
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());         
        
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
        $resizer = new Resizer($this->obtainMockImagePath(),$stubConfiguration);    
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());         
        
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
        $resizer = new Resizer($this->obtainMockImagePath(),$stubConfiguration);    
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());         
        
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
        $resizer = new Resizer($this->obtainMockImagePath(),$stubConfiguration);    
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());         
        
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
        $resizer = new Resizer($this->obtainMockImagePath(),$stubConfiguration);    
        $resizer->injectFileSystem($this->obtainMockFileExistsTrue());         
        
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
        
        $stubImagePath = $this->obtainMockImagePath();
        $resizer = new Resizer($stubImagePath,$stubConfiguration);                          
        $resizer->injectFileSystem($this->obtainMockFileCacheIsMoreRecient());        
        
        $this->assertEquals($resizer->doResize(),$resizer->obtainCacheFilePath());     
    }    
    
    private function obtainMockImagePath() {
        $stubPath = $this->getMockBuilder('ImagePath')
            ->getMock();
         $stubPath->method('obtainFileName')
            ->willReturn('mf.jpg');              
         $stubPath->method('obtainFileExtension')
            ->willReturn('jpg');  
         $stubPath->method('isHttpProtocol')
            ->willReturn('http');        
            
        return $stubPath;
    }     
    
    private function obtainMockFileExistsTrue() {
        $stubFileSystem = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stubFileSystem->method('file_exists')
            ->willReturn(true);
            
        return $stubFileSystem;
    }
    
    private function obtainMockFileCacheIsMoreRecient() {
        $timeNewFile = 20100307; //07/03/2010;
        $timeCacheFile = 20100308; //08/03/2010;

        $stubFileSystem = $this->obtainMockFileExistsTrue();    
        $stubFileSystem->method('filemtime')
            ->will($this->onConsecutiveCalls($timeNewFile,$timeNewFile,$timeCacheFile,$timeNewFile));    
            
        return $stubFileSystem;            
    }
    
    private function obtainMockConfiguration($opts) {    
        $stubConfiguration = $this->getMockBuilder('Configuration')
	  ->disableOriginalConstructor()
	  //->setConstructorArgs(array($this->requiredArguments))
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
