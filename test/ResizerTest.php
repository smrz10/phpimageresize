<?php

require_once 'Resizer.php';
require_once 'ImagePath.php';
require_once 'Configuration.php';
date_default_timezone_set('Europe/Berlin');


class ResizerTest extends PHPUnit_Framework_TestCase {
    private $RequiredArguments = array('h' => 300, 'w' => 600); 

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNecessaryCollaboration() {
        $resizer = new Resizer('anyNonPathObject', 'nonConfigurationObject');
    }

    public function testInstantiation() {
        $this->assertInstanceOf('Resizer', new Resizer(new ImagePath(''), new Configuration($this->RequiredArguments)));
        #$this->assertInstanceOf('Resizer', new Resizer(new ImagePath('')));
    }

    public function testObtainLocallyCachedFilePath() {
        $configuration = new Configuration(array('w' => 800, 'h' => 600));
        $imagePath = new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler');
        $resizer = new Resizer($imagePath, $configuration);

        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('file_get_contents')
            ->willReturn('foo');

        $stub->method('file_exists')
            ->willReturn(true);

        $resizer->injectFileSystem($stub);

        $this->assertEquals('./cache/remote/mf.jpg', $resizer->obtainFilePath());

    }

    public function testLocallyCachedFilePathFail() {
        $configuration = new Configuration(array('w' => 800, 'h' => 600));
        $imagePath = new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler');
        $resizer = new Resizer($imagePath, $configuration);

        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('file_exists')
            ->willReturn(true);

        $stub->method('filemtime')
            ->willReturn(21 * 60);

        $resizer->injectFileSystem($stub);

        $this->assertEquals('./cache/remote/mf.jpg', $resizer->obtainFilePath());

    }

    public function testCreateNewPath() {
        $configuration = new Configuration($this->RequiredArguments);    
        $resizer = new Resizer(new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler'),$configuration );
    }
    
    public function testCreateNewFileIsNotCache() {
        $pathNewFile = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';
        $pathCacheFile = './cache/remote/mf_NewFile.jpg';
        $configuration = new Configuration($this->RequiredArguments);    
        $imagePath = new ImagePath($pathNewFile);
        $resizer = new Resizer($imagePath,$configuration );        

        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('file_exists')
            ->willReturn(false);
        $resizer->injectFileSystem($stub);
        
        $this->assertTrue($resizer->isNecessaryNewFile($pathNewFile,$pathCacheFile));
    }   
    
    public function testCreateNewFileCacheIsOld() {
        $pathNewFile = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';
        $pathCacheFile = './cache/remote/mf_NewFile.jpg';
        $configuration = new Configuration($this->RequiredArguments);    
        $imagePath = new ImagePath($pathNewFile);
        $resizer = new Resizer($imagePath,$configuration );        

        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('file_exists')
            ->willReturn(True);            
            
        $stub->method('filemtime')
            ->willReturn(21 * 60);      
        $resizer->injectFileSystem($stub);
        
        $this->assertTrue($resizer->isNecessaryNewFile($pathNewFile,$pathCacheFile));
    }    

    public function testNotCreateNewFileCacheIsMoreRecent() {
        $pathNewFile = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';
        $pathCacheFile = './cache/remote/mf_NewFile.jpg';
        $configuration = new Configuration($this->RequiredArguments);    
        $imagePath = new ImagePath($pathNewFile);
        $resizer = new Resizer($imagePath,$configuration );        
        
        $timeNewFile = 30;
        $timeCacheFile = 28;

        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('file_exists')
            ->willReturn(True);                        
        $stub->method('filemtime')
            ->will($this->onConsecutiveCalls($timeNewFile,$timeCacheFile,$timeNewFile));
        $resizer->injectFileSystem($stub);
        
        $this->assertFalse($resizer->isNecessaryNewFile($pathNewFile,$pathCacheFile));
    }         

    public function testComposeNewPathRequerisArguments() {      
        $stubPath = $this->getMockBuilder('ImagePath')
            ->getMock();
        $stubPath->method('obtainFileExtension')
            ->willReturn('jpg');  
        $stubPath->method('isHttpProtocol')
            ->willReturn('http');                              
        
        $configuration = new Configuration($this->RequiredArguments);            
        $resizer = new Resizer($stubPath,$configuration);
        
        $stubFileSystem = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stubFileSystem->method('file_exists')
            ->willReturn(True);
        $resizer->injectFileSystem($stubFileSystem);    
                       
        $height = $this->RequiredArguments['h'];
        $width = $this->RequiredArguments['w'];        
        $newPath = $configuration->obtainCache().md5_file($resizer->obtainFilePath()).'_w'.$width.'_h'.$height.'.jpg';                                    
        
        $this->assertEquals($newPath, $resizer->composeNewPath());
    }
    
    public function testComposeNewPathOnlyOutputFilename() {
        $newPath = 'mf_e_dio';
        
        $stubPath = $this->getMockBuilder('ImagePath')
            ->getMock();
        $stubPath->method('obtainFileExtension')
            ->willReturn('jpg');  
        $stubPath->method('isHttpProtocol')
            ->willReturn('http');                              
        
        $configuration = new Configuration(array('output-filename'=>$newPath));            
        $resizer = new Resizer($stubPath,$configuration);
        
        $stubFileSystem = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stubFileSystem->method('file_exists')
            ->willReturn(True);
        $resizer->injectFileSystem($stubFileSystem);                                        
                
        
        $this->assertEquals($newPath, $resizer->composeNewPath());        
    }    
    
    public function testComposeNewPathWithScaleAndWidth() {
        $stubPath = $this->getMockBuilder('ImagePath')
            ->getMock();
        $stubPath->method('obtainFileExtension')
            ->willReturn('jpg');  
        $stubPath->method('isHttpProtocol')
            ->willReturn('http');                              
        
        $opts = array('scale'=>true, 'w'=>125);
        $configuration = new Configuration($opts);            
        $resizer = new Resizer($stubPath,$configuration);
        
        $stubFileSystem = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stubFileSystem->method('file_exists')
            ->willReturn(True);
        $resizer->injectFileSystem($stubFileSystem);                           

        $width = $opts['w'];        
        $newPath = $configuration->obtainCache().md5_file($resizer->obtainFilePath()).'_w'.$width.'_sc'.'.jpg';                                    
        
        $this->assertEquals($newPath, $resizer->composeNewPath());   
    }
    
    public function testComposeNewPathWithCropAndHeight() {
        $stubPath = $this->getMockBuilder('ImagePath')
            ->getMock();
        $stubPath->method('obtainFileExtension')
            ->willReturn('jpg');  
        $stubPath->method('isHttpProtocol')
            ->willReturn('http');                              
        
        $opts = array('crop'=>true, 'h'=>325);
        $configuration = new Configuration($opts);            
        $resizer = new Resizer($stubPath,$configuration);
        
        $stubFileSystem = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stubFileSystem->method('file_exists')
            ->willReturn(True);
        $resizer->injectFileSystem($stubFileSystem);                           

        $height = $opts['h'];        
        $newPath = $configuration->obtainCache().md5_file($resizer->obtainFilePath()).'_h'.$height.'_cp'.'.jpg';                                    
        
        $this->assertEquals($newPath, $resizer->composeNewPath());       
    }        

    public function testComposeNewPathWithCropAndScaleAndHeight() {
        $stubPath = $this->getMockBuilder('ImagePath')
            ->getMock();
        $stubPath->method('obtainFileExtension')
            ->willReturn('jpg');  
        $stubPath->method('isHttpProtocol')
            ->willReturn('http');                              
        
        $opts = array('crop'=>true, 'scale'=>true, 'h'=>7310);
        $configuration = new Configuration($opts);            
        $resizer = new Resizer($stubPath,$configuration);
        
        $stubFileSystem = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stubFileSystem->method('file_exists')
            ->willReturn(True);
        $resizer->injectFileSystem($stubFileSystem);                           

        $height = $opts['h'];        
        $newPath = $configuration->obtainCache().md5_file($resizer->obtainFilePath()).'_h'.$height.'_cp_sc'.'.jpg';                                    
        
        $this->assertEquals($newPath, $resizer->composeNewPath());           
    }            

    public function testComposeNewPathWithCropAndOutputFilename() {
        $newPath = 'mf_e_dio';
        $width = 300;

        $stubPath = $this->getMockBuilder('ImagePath')
            ->getMock();
        $stubPath->method('obtainFileExtension')
            ->willReturn('jpg');  
        $stubPath->method('isHttpProtocol')
            ->willReturn('http');                            

        $configuration = new Configuration(array('crop'=>true, 'w'=>$width, 'output-filename'=>$newPath));            
        $resizer = new Resizer($stubPath,$configuration);
        
        $stubFileSystem = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stubFileSystem->method('file_exists')
            ->willReturn(True);
        $resizer->injectFileSystem($stubFileSystem);                           
        
        $this->assertEquals($newPath, $resizer->composeNewPath());                   
    }  
    
    public function testDefaultShellWithRequiredArguments() {
        $pathNewFile = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';   

        $stubPath = $this->getMockBuilder('ImagePath')
            ->getMock();
        $stubPath->method('obtainFileExtension')
            ->willReturn('jpg');  
        $stubPath->method('isHttpProtocol')
            ->willReturn('http');            
        $stubPath->method('sanitizedPath')
            ->willReturn(urldecode($pathNewFile));             

        $configuration = new Configuration($this->RequiredArguments);            
        $resizer = new Resizer($stubPath,$configuration);    
        
        $stubFileSystem = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stubFileSystem->method('file_exists')
            ->willReturn(True);
        $resizer->injectFileSystem($stubFileSystem);         
        
        
        $command = 'convert ' . escapeshellarg(urldecode($pathNewFile)) .
                    ' -thumbnail x' . $this->RequiredArguments['w'] .
                    ' -quality ' . escapeshellarg('90') . ' ' .
                    escapeshellarg($resizer->composeNewPath());  
        
        $this->assertEquals($command, $resizer->defaultShellCommand());        
    }  
    
    public function testDefaultShellWithRequiredArgumentsAndMaxOnly() {
        $pathNewFile = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';   
        $opts = array_merge($this->RequiredArguments, array('maxOnly' => true));
        

        $stubPath = $this->getMockBuilder('ImagePath')
            ->getMock();
        $stubPath->method('obtainFileExtension')
            ->willReturn('jpg');  
        $stubPath->method('isHttpProtocol')
            ->willReturn('http');            
        $stubPath->method('sanitizedPath')
            ->willReturn(urldecode($pathNewFile));             

        $configuration = new Configuration($opts);            
        $resizer = new Resizer($stubPath,$configuration);    
        
        $stubFileSystem = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stubFileSystem->method('file_exists')
            ->willReturn(True);
        $resizer->injectFileSystem($stubFileSystem);         
        
        
        $command = 'convert ' . escapeshellarg(urldecode($pathNewFile)) .
                    ' -thumbnail x' . $this->RequiredArguments['w'] .
                    '\> -quality ' . escapeshellarg('90') . ' ' .
                    escapeshellarg($resizer->composeNewPath());  
        
        $this->assertEquals($command, $resizer->defaultShellCommand());        
    }    
    
    public function testDefaultShellWithMaxOnlyAndWidth() {
        $pathNewFile = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';   
        $opts = array('maxOnly' => true, 'w' => 730);
        

        $stubPath = $this->getMockBuilder('ImagePath')
            ->getMock();
        $stubPath->method('obtainFileExtension')
            ->willReturn('jpg');  
        $stubPath->method('isHttpProtocol')
            ->willReturn('http');            
        $stubPath->method('sanitizedPath')
            ->willReturn(urldecode($pathNewFile));             

        $configuration = new Configuration($opts);            
        $resizer = new Resizer($stubPath,$configuration);    
        
        $stubFileSystem = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stubFileSystem->method('file_exists')
            ->willReturn(True);
        $resizer->injectFileSystem($stubFileSystem);         
        
        
        $command = 'convert ' . escapeshellarg(urldecode($pathNewFile)) .
                    ' -thumbnail 730' .
                    '\> -quality ' . escapeshellarg('90') . ' ' .
                    escapeshellarg($resizer->composeNewPath());  
        
        $this->assertEquals($command, $resizer->defaultShellCommand());        
    }   
    
    public function testDefaultShellWithoutWidth() {
        $pathNewFile = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';   
        $opts = array('h' => 730);
        

        $stubPath = $this->getMockBuilder('ImagePath')
            ->getMock();
        $stubPath->method('obtainFileExtension')
            ->willReturn('jpg');  
        $stubPath->method('isHttpProtocol')
            ->willReturn('http');            
        $stubPath->method('sanitizedPath')
            ->willReturn(urldecode($pathNewFile));             

        $configuration = new Configuration($opts);            
        $resizer = new Resizer($stubPath,$configuration);    
        
        $stubFileSystem = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stubFileSystem->method('file_exists')
            ->willReturn(True);
        $resizer->injectFileSystem($stubFileSystem);         
        
        
        $command = 'convert ' . escapeshellarg(urldecode($pathNewFile)) .
                    ' -thumbnail x' .
                    ' -quality ' . escapeshellarg('90') . ' ' .
                    escapeshellarg($resizer->composeNewPath());  
        
        $this->assertEquals($command, $resizer->defaultShellCommand());        
    }    
    
    public function testDefaultShellWithMaxOnlyAndDimensionsAndQuality() {
        $pathNewFile = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';   
        $opts = array('maxOnly' => true, 'h' => 2010 , 'w' => 730, 'quality' => 96);
        

        $stubPath = $this->getMockBuilder('ImagePath')
            ->getMock();
        $stubPath->method('obtainFileExtension')
            ->willReturn('jpg');  
        $stubPath->method('isHttpProtocol')
            ->willReturn('http');            
        $stubPath->method('sanitizedPath')
            ->willReturn(urldecode($pathNewFile));             

        $configuration = new Configuration($opts);            
        $resizer = new Resizer($stubPath,$configuration);    
        
        $stubFileSystem = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stubFileSystem->method('file_exists')
            ->willReturn(True);
        $resizer->injectFileSystem($stubFileSystem);         
        
        
        $command = 'convert ' . escapeshellarg(urldecode($pathNewFile)) .
                    ' -thumbnail x730' .
                    '\> -quality ' . escapeshellarg('96') . ' ' .
                    escapeshellarg($resizer->composeNewPath());  
        
        $this->assertEquals($command, $resizer->defaultShellCommand());        
    }       
}
