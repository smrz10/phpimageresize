<?php
require_once 'ImagePath.php';

class ImagePathTest extends PHPUnit_Framework_TestCase {

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

}
