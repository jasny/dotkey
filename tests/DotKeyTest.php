<?php

declare(strict_types=1);

namespace Jasny\DotKey\Tests;

use Jasny\DotKey\DotKey;
use Jasny\DotKey\ResolveException;
use PHPUnit\Framework\TestCase;

class DotKeyTest extends TestCase
{
    public function testWithInvalidSubject()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Subject should be an array or object; string given");

        DotKey::on('foo');
    }

    public function subjectProvider()
    {
        $array = ['a' => ['b' => ['x' => 'y', 'n' => null]]];
        $object = (object)['a' => (object)['b' => (object)['x' => 'y', 'n' => null]]];
        $arrayAccess = new \ArrayObject([
            'a' => new \ArrayObject([
                'b' => new \ArrayObject(['x' => 'y', 'n' => null])
            ])
        ]);
        $mixed = new \ArrayObject(['a' => (object)['b' => ['x' => 'y', 'n' => null]]]);

        return [
            'array' => [$array, $array['a']['b']],
            'object' => [$object, $object->a->b],
            'ArrayAccess' => [$arrayAccess, $arrayAccess['a']['b']],
            'mixed' => [$mixed, $mixed['a']->b],
        ];
    }

    public function pathProvider()
    {
        return [
            'a.b.*'   => ['.', 'a.b.x', 'a.b.z'],
            'a/b/*'   => ['/', 'a/b/x', 'a/b/z'],
            '/a/b/*'  => ['/', '/a/b/x', '/a/b/z'],
            '/a/b/*/' => ['/', '/a/b/x/', '/a/b/z/'],
            'a::b::*' => ['::', 'a::b::x', 'a::b::z'],
        ];
    }

    public function privateProvider()
    {
        $subject = new class () {
            private $a = ['b' => 1];
        };

        return [
            'a' => [$subject, 'a', 'a'],
            'a.b' => [$subject, 'a.b', 'a'],
            's.a' => [['s' => $subject], 's.a', 's.a'],
        ];
    }

    /**
     * @dataProvider subjectProvider
     */
    public function testExists($subject)
    {
        $this->assertTrue(DotKey::on($subject)->exists("a.b.x"));
        $this->assertTrue(DotKey::on($subject)->exists("a.b.n"));
        $this->assertFalse(DotKey::on($subject)->exists("a.b.z"));
        $this->assertFalse(DotKey::on($subject)->exists("a.b.x.o"));
    }

    /**
     * @dataProvider pathProvider
     */
    public function testExistsWithDelimiter(string $delimiter, string $abx, string $abz)
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->assertTrue(DotKey::on($subject)->exists($abx, $delimiter));
        $this->assertFalse(DotKey::on($subject)->exists($abz, $delimiter));
    }

    public function testExistsPrivateProperty()
    {
        $subject = new class () {
            private $a = 'apple';
            private $b = ['x' => 'banana'];
            public $n = null;
        };

        $this->assertFalse(DotKey::on($subject)->exists("a"));
        $this->assertFalse(DotKey::on($subject)->exists("b"));
        $this->assertFalse(DotKey::on($subject)->exists("b.x"));
        $this->assertTrue(DotKey::on($subject)->exists("n"));
    }

    public function testExistsWithInvalidDelimiter()
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Delimiter can't be an empty string");

        DotKey::on($subject)->get('ab', '');
    }


    /**
     * @dataProvider subjectProvider
     */
    public function testGet($subject, $ab)
    {
        $this->assertEquals("y", DotKey::on($subject)->get("a.b.x"));
        $this->assertSame($ab, DotKey::on($subject)->get("a.b"));
        $this->assertNull(DotKey::on($subject)->get("a.b.z"));
    }

    /**
     * @dataProvider privateProvider
     */
    public function testGetPrivateProperty($subject, string $path, string $at)
    {
        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to get '$path': error at '$at'");

        $this->assertTrue(DotKey::on($subject)->get($path));
    }

    /**
     * @dataProvider pathProvider
     */
    public function testGetWithDelimiter(string $delimiter, string $abx, string $abz)
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->assertEquals("y", DotKey::on($subject)->get($abx, $delimiter));
        $this->assertNull(DotKey::on($subject)->get($abz, $delimiter));
    }

    /**
     * @dataProvider pathProvider
     */
    public function testGetWithUnresolvablePath(string $delimiter, string $basePath)
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];
        $path = str_replace('x', 'x' . $delimiter . 'o1' . $delimiter . 'q11', $basePath);
        $invalidPath = rtrim($basePath, $delimiter);

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to get '$path': '$invalidPath' is of type string");

        DotKey::on($subject)->get($path, $delimiter);
    }

    public function testGetWithInvalidDelimiter()
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Delimiter can't be an empty string");

        DotKey::on($subject)->get('ab', '');
    }


    public function setSubjectProvider()
    {
        return [
            'array' => [
                ['a' => ['b' => ['x' => 'y', 'n' => null]]],
                ['a' => ['b' => ['x' => 'y', 'n' => 1], 'q' => 'foo']],
            ],
            'object' => [
                (object)['a' => (object)['b' => (object)['x' => 'y', 'n' => null]]],
                (object)['a' => (object)['b' => (object)['x' => 'y', 'n' => 1], 'q' => 'foo']],
            ],
            'ArrayAccess' => [
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        'b' => new \ArrayObject(['x' => 'y', 'n' => null]),
                    ]),
                ]),
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        'b' => new \ArrayObject(['x' => 'y', 'n' => 1]),
                        'q' => 'foo',
                    ]),
                ]),
            ],
            'mixed' => [
                new \ArrayObject(['a' => (object)['b' => ['x' => 'y', 'n' => null]]]),
                new \ArrayObject(['a' => (object)['b' => ['x' => 'y', 'n' => 1], 'q' => 'foo']]),
            ],
        ];
    }

    /**
     * @dataProvider setSubjectProvider
     */
    public function testSet($subject, $expected)
    {
        $resultAdded = DotKey::on($subject)->set("a.b.n", 1);

        if (is_array($subject)) {
            $this->assertIsArray($resultAdded);
        } else {
            $this->assertIsObject($resultAdded);
        }

        $result = DotKey::on($resultAdded)->set("a.q", "foo");

        if (is_object($subject)) {
            $this->assertSame($subject, $result);
        } else {
            $this->assertNotSame($subject, $result);
        }

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider pathProvider
     */
    public function testSetWithDelimiter(string $delimiter, string $abx)
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $result = DotKey::on($subject)->set($abx, 'z', $delimiter);

        $this->assertEquals(['a' => ['b' => ['x' => 'z']]], $result);
    }

    /**
     * @dataProvider pathProvider
     */
    public function testSetWithNonExistingPath(string $delimiter, string $basePath)
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];
        $path = str_replace('x', 'c' . $delimiter . 'd', $basePath);
        $invalidPath = str_replace('x', 'c', rtrim($basePath, $delimiter));

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to set '$path': '$invalidPath' doesn't exist");

        DotKey::on($subject)->set($path, '', $delimiter);
    }

    /**
     * @dataProvider pathProvider
     */
    public function testSetWithUnresolvablePath(string $delimiter, string $basePath)
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];
        $path = str_replace('x', 'x' . $delimiter . 'o1' . $delimiter . 'q11', $basePath);
        $invalidPath = rtrim($basePath, $delimiter);

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to set '$path': '$invalidPath' is of type string");

        DotKey::on($subject)->set($path, '', $delimiter);
    }

    /**
     * @dataProvider privateProvider
     */
    public function testSetPrivateProperty($subject, string $path, string $at)
    {
        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to set '$path': error at '$at'");

        $this->assertTrue(DotKey::on($subject)->set($path, 10));
    }

    public function testSetWithInvalidDelimiter()
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Delimiter can't be an empty string");

        DotKey::on($subject)->set('ab', 1, '');
    }


    /**
     * @dataProvider setSubjectProvider
     */
    public function testPut($subject, $expected)
    {
        $resultAdded = DotKey::on($subject)->put("a.b.n", 1);

        if (is_array($subject)) {
            $this->assertIsArray($resultAdded);
        } else {
            $this->assertIsObject($resultAdded);
        }

        $result = DotKey::on($resultAdded)->put("a.q", "foo");

        if (is_object($subject)) {
            $this->assertSame($subject, $result);
        } else {
            $this->assertNotSame($subject, $result);
        }

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider pathProvider
     */
    public function testPutWithDelimiter(string $delimiter, string $abx)
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $result = DotKey::on($subject)->put($abx, 'z', $delimiter);

        $this->assertEquals(['a' => ['b' => ['x' => 'z']]], $result);
    }

    public function putSubjectProvider()
    {
        return [
            'array' => [
                ['a' => ['b' => ['x' => 'y']]],
                ['a' => ['b' => ['x' => 'y'], 'q' => ['n' => 1]]],
                ['a' => ['b' => ['x' => 'y'], 'q' => ['n' => 1]]],
                ['a' => ['b' => ['x' => 'y'], 'q' => (object)['n' => 1]]],
            ],
            'object' => [
                (object)['a' => (object)['b' => (object)['x' => 'y']]],
                (object)['a' => (object)['b' => (object)['x' => 'y'], 'q' => (object)['n' => 1]]],
                (object)['a' => (object)['b' => (object)['x' => 'y'], 'q' => ['n' => 1]]],
                (object)['a' => (object)['b' => (object)['x' => 'y'], 'q' => (object)['n' => 1]]],
            ],
            'ArrayAccess' => [
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        'b' => new \ArrayObject(['x' => 'y']),
                    ]),
                ]),
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        'b' => new \ArrayObject(['x' => 'y']),
                        'q' => ['n' => 1],
                    ]),
                ]),
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        'b' => new \ArrayObject(['x' => 'y']),
                        'q' => ['n' => 1],
                    ]),
                ]),
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        'b' => new \ArrayObject(['x' => 'y']),
                        'q' => (object)['n' => 1],
                    ]),
                ]),
            ],
            'mixed' => [
                new \ArrayObject(['a' => (object)['b' => ['x' => 'y']]]),
                new \ArrayObject(['a' => (object)['b' => ['x' => 'y'], 'q' => ['n' => 1]]]),
                new \ArrayObject(['a' => (object)['b' => ['x' => 'y'], 'q' => ['n' => 1]]]),
                new \ArrayObject(['a' => (object)['b' => ['x' => 'y'], 'q' => (object)['n' => 1]]]),
            ],
        ];
    }

    /**
     * @dataProvider putSubjectProvider
     */
    public function testPutCreate($subject, $expected)
    {
        $result = DotKey::on($subject)->put("a.q.n", 1);

        if (is_object($subject)) {
            $this->assertSame($subject, $result);
        } else {
            $this->assertNotSame($subject, $result);
        }

        $this->assertEquals($expected, $result);
    }

    public function testPutCreateDeep()
    {
        $subject = ['a' => ['q' => 1]];
        $result = DotKey::on($subject)->put("a.b.c.d.e.f", 1);

        $expected = ['a' => ['q' => 1, 'b' => ['c' => ['d' => ['e' => ['f' => 1]]]]]];
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider putSubjectProvider
     */
    public function testPutCreateForceAssoc($subject, $_, $expected)
    {
        $result = DotKey::on($subject)->put("a.q.n", 1, '.', true);

        if (is_object($subject)) {
            $this->assertSame($subject, $result);
        } else {
            $this->assertNotSame($subject, $result);
        }

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider putSubjectProvider
     */
    public function testPutCreateForceObject($subject, $_, $__, $expected)
    {
        $result = DotKey::on($subject)->put("a.q.n", 1, '.', false);

        if (is_object($subject)) {
            $this->assertSame($subject, $result);
        } else {
            $this->assertNotSame($subject, $result);
        }

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider privateProvider
     */
    public function testPutPrivateProperty($subject, string $path, string $at)
    {
        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to put '$path': error at '$at'");

        $this->assertTrue(DotKey::on($subject)->put($path, 10));
    }

    public function putOverwriteSubjectProvider()
    {
        return [
            'array' => [
                ['a' => ['b' => 42]],
                ['a' => ['b' => ['n' => 1]]],
            ],
            'object' => [
                (object)['a' => (object)['b' => 42]],
                (object)['a' => (object)['b' => (object)['n' => 1]]],
            ],
            'ArrayAccess' => [
                new \ArrayObject([
                    'a' => new \ArrayObject(['b' => 42]),
                ]),
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        'b' => ['n' => 1],
                    ]),
                ]),
            ],
            'mixed' => [
                new \ArrayObject(['a' => (object)['b' => 42]]),
                new \ArrayObject(['a' => (object)['b' => ['n' => 1]]]),
            ],
        ];
    }

    /**
     * @dataProvider putOverwriteSubjectProvider
     */
    public function testPutOverwriteExisting($subject, $expected)
    {
        $result = DotKey::on($subject)->put("a.b.n", 1, '.');

        if (is_object($subject)) {
            $this->assertSame($subject, $result);
        } else {
            $this->assertNotSame($subject, $result);
        }

        $this->assertEquals($expected, $result);
    }

    public function testPutWithInvalidDelimiter()
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Delimiter can't be an empty string");

        DotKey::on($subject)->put('ab', 1, '');
    }


    public function removeSubjectProvider()
    {
        return [
            'array' => [
                ['a' => ['b' => ['x' => 'y', 'n' => null]]],
                ['a' => ['b' => ['x' => 'y']]],
                ['a' => []],
            ],
            'object' => [
                (object)['a' => (object)['b' => (object)['x' => 'y', 'n' => null]]],
                (object)['a' => (object)['b' => (object)['x' => 'y']]],
                (object)['a' => (object)[]],
            ],
            'ArrayAccess' => [
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        'b' => new \ArrayObject(['x' => 'y', 'n' => null]),
                    ]),
                ]),
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        'b' => new \ArrayObject(['x' => 'y']),
                    ]),
                ]),
                new \ArrayObject([
                    'a' => new \ArrayObject([]),
                ]),
            ],
            'mixed' => [
                new \ArrayObject(['a' => (object)['b' => ['x' => 'y', 'n' => null]]]),
                new \ArrayObject(['a' => (object)['b' => ['x' => 'y']]]),
                new \ArrayObject(['a' => (object)[]]),
            ],
        ];
    }
    
    /**
     * @dataProvider removeSubjectProvider
     */
    public function testRemove($subject, $expected)
    {
        $result = DotKey::on($subject)->remove("a.b.n");

        if (is_object($subject)) {
            $this->assertSame($subject, $result);
        } else {
            $this->assertNotSame($subject, $result);
        }

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider removeSubjectProvider
     */
    public function testRemoveBlock($subject, $_, $expected)
    {
        $result = DotKey::on($subject)->remove("a.b");

        if (is_object($subject)) {
            $this->assertSame($subject, $result);
        } else {
            $this->assertNotSame($subject, $result);
        }

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider pathProvider
     */
    public function testRemoveWithDelimiter(string $delimiter, string $abx)
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $result = DotKey::on($subject)->remove($abx, $delimiter);

        $this->assertEquals(['a' => ['b' => []]], $result);
    }

    /**
     * @dataProvider removeSubjectProvider
     */
    public function testRemoveWithNonExistingPath($subject)
    {
        $result = DotKey::on($subject)->remove('a.r.d');

        $this->assertSame($subject, $result);
    }

    /**
     * @dataProvider pathProvider
     */
    public function testRemoveWithUnresolvablePath(string $delimiter, string $basePath)
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];
        $path = str_replace('x', 'x' . $delimiter . 'o1' . $delimiter . 'q11', $basePath);
        $invalidPath = rtrim($basePath, $delimiter);

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to remove '$path': '$invalidPath' is of type string");

        DotKey::on($subject)->remove($path, $delimiter);
    }

    /**
     * @dataProvider privateProvider
     */
    public function testRemovePrivateProperty($subject, string $path, string $at)
    {
        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to remove '$path': error at '$at'");

        $this->assertTrue(DotKey::on($subject)->remove($path));
    }

    public function testRemoveWithInvalidDelimiter()
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Delimiter can't be an empty string");

        DotKey::on($subject)->remove('ab', '');
    }
}
