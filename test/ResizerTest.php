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
}
