<?php

require_once '_helpers_test.php';

class ComposePathTest extends PHPUnit_Framework_TestCase {
    private $requiredArguments = array('h' => 300, 'w' => 600); 
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testNecessaryCollaboration() {
        $resizer = new ComposePath('anyNonPathObject', 'anyNonPathObject', 'nonConfigurationObject');
    }

    public function testInstantiation() {	
	$imagePath = './cache/remote/image_original';
	$fileExtension = 'jpg';
        $this->assertInstanceOf('ComposePath', new ComposePath($imagePath,$fileExtension, new configuration($this->requiredArguments)));
    } 

    public function testRequerisArguments() {            
        $configuration = new Configuration($this->requiredArguments);            
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);        
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                    
        
        $filePath = $imagePath->ObtainFilePath($configuration->obtainRemote());
        $fileExtension = $imagePath->obtainFileExtension();
        $composeNewPath = new ComposePath($filePath, $fileExtension, $configuration);
                       
        $height = $this->requiredArguments['h'];
        $width = $this->requiredArguments['w'];        
        $newPath = $configuration->obtainCache().md5_file($filePath).'_w'.$width.'_h'.$height.'.jpg';                                    
        
        $this->assertEquals($newPath, $composeNewPath->composeNewPath());
    }    
    
    public function testOnlyOutputFilename() {
        $newPath = 'mf_e_dio';
 
        $configuration = new Configuration(array('output-filename'=>$newPath));            
	$cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);        
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                            
        
        $filePath = $imagePath->ObtainFilePath($configuration->obtainRemote());
        $fileExtension = $imagePath->obtainFileExtension();
        $composeNewPath = new ComposePath($filePath, $fileExtension, $configuration);        
        
        $this->assertEquals($newPath, $composeNewPath->composeNewPath());        
    }     
    
    public function testWithScaleAndWidth() { 
        $opts = array('scale'=>true, 'w'=>125);
        
        $configuration = new Configuration($opts);            
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);        
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                            
        
        $filePath = $imagePath->ObtainFilePath($configuration->obtainRemote());
        $fileExtension = $imagePath->obtainFileExtension();
        $composeNewPath = new ComposePath($filePath, $fileExtension, $configuration);                

        $width = $opts['w'];        
        $newPath = $configuration->obtainCache().md5_file($filePath).'_w'.$width.'_sc'.'.jpg';                                    
        
        $this->assertEquals($newPath, $composeNewPath->composeNewPath());   
    } 
    
    public function testWithCropAndHeight() {     
        $opts = array('crop'=>true, 'h'=>325);
        
        $configuration = new Configuration($opts);            
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                 
        
        $filePath = $imagePath->ObtainFilePath($configuration->obtainRemote());
        $fileExtension = $imagePath->obtainFileExtension();
        $composeNewPath = new ComposePath($filePath, $fileExtension, $configuration);                        

        $height = $opts['h'];        
        $newPath = $configuration->obtainCache().md5_file($filePath).'_h'.$height.'_cp'.'.jpg';                                    
        
        $this->assertEquals($newPath, $composeNewPath->composeNewPath());       
    }   
    
    public function testWithCropAndScaleAndHeight() {      
        $opts = array('crop'=>true, 'scale'=>true, 'h'=>7310);
        
        $configuration = new Configuration($opts);            
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                         
        
        $filePath = $imagePath->ObtainFilePath($configuration->obtainRemote());
        $fileExtension = $imagePath->obtainFileExtension();
        $composeNewPath = new ComposePath($filePath, $fileExtension, $configuration);                                

        $height = $opts['h'];        
        $newPath = $configuration->obtainCache().md5_file($filePath).'_h'.$height.'_cp_sc'.'.jpg';                                    
        
        $this->assertEquals($newPath, $composeNewPath->composeNewPath());           
    }                

    public function testPathWithCropAndOutputFilename() {
        $newPath = 'mf_e_dio';
        $width = 300;     

        $configuration = new Configuration(array('crop'=>true, 'w'=>$width, 'output-filename'=>$newPath));            
        $cache = new Cache($configuration->obtainCacheMinutes());
        $imagePath = new ImagePath(URL_IMAGE_MF, $cache);  
        
        $stub = $this->obtainMockFileExistsTrue();    
        $cache->injectFileSystem($stub);                                                 
        
        $filePath = $imagePath->ObtainFilePath($configuration->obtainRemote());
        $fileExtension = $imagePath->obtainFileExtension();
        $composeNewPath = new ComposePath($filePath, $fileExtension, $configuration);                                        
        
        $this->assertEquals($newPath, $composeNewPath->composeNewPath());                   
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