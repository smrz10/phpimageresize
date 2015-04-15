<?php
require_once 'ImagePath.php';

class ImagePathTest extends PHPUnit_Framework_TestCase {
    private $requiredArguments = array('h' => 300, 'w' => 600);      

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNecessaryCollaboration() {
	$url = 'https://www.google.com/';
        $imagePath = new ImagePath($url, 'nonCacheObject');
    }

    public function testInstantiation() {
	$url = 'https://www.google.com/';
        $this->assertInstanceOf('ImagePath', new ImagePath($url,new Cache(30)));
    }

    public function testIsSanitizedAtInstantiation() {
        $url = 'https://www.google.com/webhp?sourceid=chrome-instant&ion=1&espv=2&ie=UTF-8#safe=off&q=php%20define%20dictionary';
        $expected = 'https://www.google.com/webhp?sourceid=chrome-instant&ion=1&espv=2&ie=UTF-8#safe=off&q=php define dictionary';

        $imagePath = new ImagePath($url, new Cache(30));

        $this->assertEquals($expected, $imagePath->sanitizedPath());
    }

    public function testIsHttpProtocol() {
        $url = 'https://example.com';
        $cache = new Cache(30);

        $imagePath = new ImagePath($url,$cache);

        $this->assertTrue($imagePath->isHttpProtocol());

        $imagePath = new ImagePath('ftp://example.com',$cache);

        $this->assertFalse($imagePath->isHttpProtocol());

        $imagePath = new ImagePath(null,$cache);

        $this->assertFalse($imagePath->isHttpProtocol());
    }

    public function testObtainFileName() {
        $url = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';

        $imagePath = new ImagePath($url,new Cache(30));

        $this->assertEquals('mf.jpg', $imagePath->obtainFileName());
    }  
    
    /**
     * @expectedException RuntimeException
     */
    public function testObtainFilePathErrorNotFile() {	
        $configuration = new Configuration($this->requiredArguments);
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);                
        
        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('file_exists')
            ->willReturn(false);                    
        $cache->injectFileSystem($stub);
        
        $imagePath->obtainFilePath($configuration->obtainRemote());            
    }

    public function testObtainLocallyCachedFilePath() {
        $configuration = new Configuration(array('w' => 800, 'h' => 600));
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);

        $stub = $this->obtainMockFileExistsTrue();
        $stub->method('file_get_contents')
            ->willReturn('foo');            
        $cache->injectFileSystem($stub);

        $this->assertEquals('./cache/remote/mf.jpg', $imagePath->obtainFilePath($configuration->obtainRemote()));
    }
    
    public function testLocallyCachedFilePathFail() {
        $configuration = new Configuration(array('w' => 800, 'h' => 600));
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        
        $stub = $this->obtainMockFileExistsTrue();
        $stub->method('filemtime')
            ->willReturn(21 * 60);        
        $cache->injectFileSystem($stub);

        $this->assertEquals('./cache/remote/mf.jpg', $imagePath->obtainFilePath($configuration->obtainRemote()));
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
}
