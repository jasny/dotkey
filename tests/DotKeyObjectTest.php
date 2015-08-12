<?php

namespace Jasny;

/**
 * Unit tests for Jasny\DotKey on an object
 */
class DotKeyObjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var object
     */
    protected $object;
    
    /**
     * @var DotKey
     */
    protected $dotkey;

    /**
     * Turn an associated array into an object (deep)
     * 
     * @param array $array
     * @return object
     */
    protected static function objectify(array $array)
    {
        $object = (object)$array;
        
        foreach ($object as $key => &$value) {
            if (is_array($value) && !empty($value) && is_string(key($value))) $value = self::objectify($value);
        }
        
        return $object;
    }

    /**
     * Get the value of a protected property
     * 
     * @param string $property
     * @return midex
     */
    protected function getProperty($property)
    {
        $this->reflection = new \ReflectionObject($this->dotkey);
        $propRefl = $this->reflection->getProperty($property);
        $propRefl->setAccessible(true);

        return $propRefl->getValue($this->dotkey);
    }
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = self::objectify(['a' => ['b' => ['x' => 'y']]]);
        $this->dotkey = new DotKey($this->object);
    }
    
    
    /**
     * Test Jasny\DotKey::__construct
     */
    public function testConctruct()
    {
        $this->assertFalse($this->dotkey->assoc);
    }
    

    /**
     * Test Jasny\DotKey::exists
     */
    public function testExists()
    {
        $this->assertTrue($this->dotkey->exists("a.b.x"));
        $this->assertFalse($this->dotkey->exists("a.b.z"));
        $this->assertFalse($this->dotkey->exists("a.b.x.o"));
    }
    

    /**
     * Test Jasny\DotKey::get
     */
    public function testGet()
    {
        $this->assertSame("y", $this->dotkey->get("a.b.x"));
        $this->assertEquals((object)["x" => "y"], $this->dotkey->get("a.b"));
        $this->assertNull($this->dotkey->get("a.b.z"));
    }

    /**
     * Test Jasny\DotKey::get
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Unable to get 'a.b.x.o': 'a.b.x' is a string
     */
    public function testGet_invalid()
    {
        $this->dotkey->get("a.b.x.o");
    }
    

    /**
     * Test Jasny\DotKey::set
     */
    public function testSet_add()
    {
        $expect = self::objectify(['a' => ['b' => ['x' => 'y', 'q' => 'foo']]]);
        $this->assertSame($this->object, $this->dotkey->set("a.b.q", "foo"));
        $this->assertEquals($expect, $this->object);
    }
    
    /**
     * Test Jasny\DotKey::set
     */
    public function testSet_replace()
    {
        $expect = self::objectify(['a' => ['b' => ['x' => 'bar']]]);
        $this->assertSame($this->object, $this->dotkey->set("a.b.x", "bar"));
        $this->assertEquals($expect, $this->object);
    }
    
    /**
     * Test Jasny\DotKey::set
     */
    public function testSet_block()
    {
        $expect = self::objectify(['a' => ['b' => ['x' => 'y'], 'd' => ['p' => 1]]]);
        $this->assertSame($this->object, $this->dotkey->set("a.d", (object)['p' => 1]));
        $this->assertEquals($expect, $this->object);
    }
    
    /**
     * Test Jasny\DotKey::set
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Unable to set 'a.c.x': 'a.c' doesn't exist
     */
    public function testSet_create()
    {
        $this->dotkey->set("a.c.x", "bar");
    }

    /**
     * Test Jasny\DotKey::set
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Unable to set 'a.b.x.o': 'a.b.x' is a string
     */
    public function testSet_invalid()
    {
        $this->dotkey->set("a.b.x.o", "qux");
    }
    
    
    /**
     * Test Jasny\DotKey::put
     */
    public function testPut_add()
    {
        $expect = self::objectify(['a' => ['b' => ['x' => 'y', 'q' => 'foo']]]);
        $this->assertSame($this->object, $this->dotkey->put("a.b.q", "foo"));
        $this->assertEquals($expect, $this->object);
    }
    
    /**
     * Test Jasny\DotKey::put
     */
    public function testPut_replace()
    {
        $expect = self::objectify(['a' => ['b' => ['x' => 'bar']]]);
        $this->assertSame($this->object, $this->dotkey->put("a.b.x", "bar"));
        $this->assertEquals($expect, $this->object);
    }
    
    /**
     * Test Jasny\DotKey::put
     */
    public function testPut_block()
    {
        $expect = self::objectify(['a' => ['b' => ['x' => 'y'], 'd' => ['p' => 1]]]);
        $this->assertSame($this->object, $this->dotkey->put("a.d", (object)['p' => 1]));
        $this->assertEquals($expect, $this->object);
    }
    
    /**
     * Test Jasny\DotKey::put
     */
    public function testPut_create()
    {
        $expect = self::objectify(['a' => ['b' => ['x' => 'y'], 'c' => ['x' => 'bar']]]);
        $this->assertSame($this->object, $this->dotkey->put("a.c.x", "bar"));
        $this->assertEquals($expect, $this->object);
    }
    
    /**
     * Test Jasny\DotKey::put
     */
    public function testPut_createAssoc()
    {
        $this->dotkey->assoc = true;
        
        $expect = (object)['a' => (object)['b' => (object)['x' => 'y'], 'c' => ['x' => 'bar']]];
        $this->assertSame($this->object, $this->dotkey->put("a.c.x", "bar"));
        $this->assertEquals($expect, $this->object);
    }
    
    /**
     * Test Jasny\DotKey::put
     */
    public function testPut_createObject()
    {
        $this->dotkey->assoc = false;
        
        $expect = self::objectify(['a' => ['b' => ['x' => 'y'], 'c' => (object)['x' => 'bar']]]);
        $this->assertSame($this->object, $this->dotkey->put("a.c.x", "bar"));
        $this->assertEquals($expect, $this->object);
    }
    
    /**
     * Test Jasny\DotKey::put
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Unable to put 'a.b.x.o': 'a.b.x' is a string
     */
    public function testPut_invalid()
    {
        $this->dotkey->put("a.b.x.o", "qux");
    }

    
    /**
     * Test Jasny\DotKey::remove
     */
    public function testRemove()
    {
        $expect = self::objectify(['a' => ['b' => (object)[]]]);
        $this->assertSame($this->object, $this->dotkey->remove("a.b.x"));
        $this->assertEquals($expect, $this->object);
    }

    /**
     * Test Jasny\DotKey::remove
     */
    public function testRemove_ignore()
    {
        $expect = self::objectify(['a' => ['b' => ['x' => 'y']]]);
        $this->assertSame($this->object, $this->dotkey->remove("a.c.z"));
        $this->assertEquals($expect, $this->object);
    }

    /**
     * Test Jasny\DotKey::remove
     */
    public function testRemove_block()
    {
        $expect = self::objectify(['a' => (object)[]]);
        $this->assertSame($this->object, $this->dotkey->remove("a.b"));
        $this->assertEquals($expect, $this->object);
    }

    /**
     * Test Jasny\DotKey::remove
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Unable to remove 'a.b.x.o': 'a.b.x' is a string
     */
    public function testRemove_invalid()
    {
        $this->dotkey->remove("a.b.x.o");
    }

    
    /**
     * Test Jasny\DotKey::on
     */
    public function testOn()
    {
        $foo = (object)['foo' => 'bar'];
        
        $this->dotkey = DotKey::on($foo);
        $this->assertSame($foo, $this->getProperty('item'));
    }
}
