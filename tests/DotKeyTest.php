<?php

/** @noinspection PhpPropertyNamingConventionInspection */

declare(strict_types=1);

namespace Jasny\DotKey\Tests;

use Jasny\DotKey\DotKey;
use Jasny\DotKey\ResolveException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DotKeyTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    public static function subjectProvider(): array
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

    /**
     * @return array<string, mixed>
     */
    public static function pathProvider(): array
    {
        return [
            'a.b.*'   => ['.', 'a.b.x', 'a.b.z'],
            'a/b/*'   => ['/', 'a/b/x', 'a/b/z'],
            '/a/b/*'  => ['/', '/a/b/x', '/a/b/z'],
            '/a/b/*/' => ['/', '/a/b/x/', '/a/b/z/'],
            'a::b::*' => ['::', 'a::b::x', 'a::b::z'],
        ];
    }

    /**
     * @noinspection PhpUnusedPrivateFieldInspection
     * @return array<string, mixed>
     */
    public static function privateProvider(): array
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

    #[DataProvider('subjectProvider')]
    public function testExists(mixed $subject): void
    {
        $this->assertTrue(DotKey::on($subject)->exists("a.b.x"));
        $this->assertTrue(DotKey::on($subject)->exists("a.b.n"));
        $this->assertFalse(DotKey::on($subject)->exists("a.b.z"));
        $this->assertFalse(DotKey::on($subject)->exists("a.b.x.o"));
    }

    #[DataProvider('pathProvider')]
    public function testExistsWithDelimiter(string $delimiter, string $abx, string $abz): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->assertTrue(DotKey::on($subject)->exists($abx, $delimiter));
        $this->assertFalse(DotKey::on($subject)->exists($abz, $delimiter));
    }

    /** @noinspection PhpUnusedPrivateFieldInspection */
    public function testExistsPrivateProperty(): void
    {
        $subject = new class () {
            private string $a = 'apple';
            private array $b = ['x' => 'banana'];
            public mixed $n = null;
        };

        $this->assertFalse(DotKey::on($subject)->exists("a"));
        $this->assertFalse(DotKey::on($subject)->exists("b"));
        $this->assertFalse(DotKey::on($subject)->exists("b.x"));
        $this->assertTrue(DotKey::on($subject)->exists("n"));
    }

    public function testExistsWithInvalidDelimiter(): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Delimiter can't be an empty string");

        DotKey::on($subject)->get('ab', '');
    }


    #[DataProvider('subjectProvider')]
    public function testGet(mixed $subject, mixed $ab): void
    {
        $this->assertEquals("y", DotKey::on($subject)->get("a.b.x"));
        $this->assertSame($ab, DotKey::on($subject)->get("a.b"));
        $this->assertNull(DotKey::on($subject)->get("a.b.z"));
    }

    #[DataProvider('privateProvider')]
    public function testGetPrivateProperty(mixed $subject, string $path, string $at): void
    {
        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to get '$path': error at '$at'");

        $this->assertTrue(DotKey::on($subject)->get($path));
    }

    #[DataProvider('pathProvider')]
    public function testGetWithDelimiter(string $delimiter, string $abx, string $abz): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->assertEquals("y", DotKey::on($subject)->get($abx, $delimiter));
        $this->assertNull(DotKey::on($subject)->get($abz, $delimiter));
    }

    #[DataProvider('pathProvider')]
    public function testGetWithUnresolvablePath(string $delimiter, string $basePath): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];
        $path = str_replace('x', 'x' . $delimiter . 'o1' . $delimiter . 'q11', $basePath);
        $invalidPath = rtrim($basePath, $delimiter);

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to get '$path': '$invalidPath' is of type string");

        DotKey::on($subject)->get($path, $delimiter);
    }

    public function testGetWithInvalidDelimiter(): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Delimiter can't be an empty string");

        DotKey::on($subject)->get('ab', '');
    }


    /**
     * @return array<string, mixed>
     */
    public static function setSubjectProvider(): array
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

    #[DataProvider('setSubjectProvider')]
    public function testSet(mixed $subject, mixed $expected): void
    {
        DotKey::on($subject)->set("a.b.n", 1);
        DotKey::on($subject)->set("a.q", "foo");

        $this->assertEquals($expected, $subject);
    }

    #[DataProvider('setSubjectProvider')]
    public function testSetOnCopy(mixed $subject, mixed $expected): void
    {
        DotKey::onCopy($subject, $copy1)->set("a.b.n", 1);
        DotKey::onCopy($copy1, $copy2)->set("a.q", "foo");

        $this->assertNotSame($subject, $copy1);
        $this->assertNotSame($copy1, $copy2);

        $this->assertEquals($expected, $copy2);
    }

    #[DataProvider('pathProvider')]
    public function testSetWithDelimiter(string $delimiter, string $abx): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        DotKey::on($subject)->set($abx, 'z', $delimiter);

        $this->assertEquals(['a' => ['b' => ['x' => 'z']]], $subject);
    }

    #[DataProvider('pathProvider')]
    public function testSetWithNonExistingPath(string $delimiter, string $basePath): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];
        $path = str_replace('x', 'c' . $delimiter . 'd', $basePath);
        $invalidPath = str_replace('x', 'c', rtrim($basePath, $delimiter));

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to set '$path': '$invalidPath' doesn't exist");

        DotKey::on($subject)->set($path, '', $delimiter);
    }

    #[DataProvider('pathProvider')]
    public function testSetWithUnresolvablePath(string $delimiter, string $basePath): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];
        $path = str_replace('x', 'x' . $delimiter . 'o1' . $delimiter . 'q11', $basePath);
        $invalidPath = rtrim($basePath, $delimiter);

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to set '$path': '$invalidPath' is of type string");

        DotKey::on($subject)->set($path, '', $delimiter);
    }

    #[DataProvider('privateProvider')]
    public function testSetPrivateProperty(mixed $subject, string $path, string $at): void
    {
        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to set '$path': error at '$at'");

        DotKey::on($subject)->set($path, 10);
    }

    public function testSetWithInvalidDelimiter(): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Delimiter can't be an empty string");

        DotKey::on($subject)->set('ab', 1, '');
    }

    public function testSetOnCopyDeep(): void
    {
        $subject = (object)['a' => (object)['b1' => (object)['x' => 'y'], 'b2' => (object)['q' => 'r']]];

        DotKey::onCopy($subject, $copy)->set("a.b1.x", 'z');

        $expectedSubject = (object)['a' => (object)['b1' => (object)['x' => 'y'], 'b2' => (object)['q' => 'r']]];
        $expectedCopy = (object)['a' => (object)['b1' => (object)['x' => 'z'], 'b2' => (object)['q' => 'r']]];

        $this->assertNotSame($subject, $copy);
        $this->assertNotSame($subject->a, $copy->a);
        $this->assertNotSame($subject->a->b1, $copy->a->b1);
        $this->assertSame($subject->a->b2, $copy->a->b2);

        $this->assertEquals($expectedSubject, $subject);
        $this->assertEquals($expectedCopy, $copy);
    }

    #[DataProvider('setSubjectProvider')]
    public function testSetOnCopyNoChange(mixed $subject): void
    {
        DotKey::onCopy($subject, $copy)->set("a.b.x", 'y');

        $this->assertSame($subject, $copy);
    }


    #[DataProvider('setSubjectProvider')]
    public function testPut(mixed $subject, mixed $expected): void
    {
        DotKey::on($subject)->put("a.b.n", 1);
        DotKey::on($subject)->put("a.q", "foo");

        $this->assertEquals($expected, $subject);
    }

    #[DataProvider('pathProvider')]
    public function testPutWithDelimiter(string $delimiter, string $abx): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        DotKey::on($subject)->put($abx, 'z', $delimiter);

        $this->assertEquals(['a' => ['b' => ['x' => 'z']]], $subject);
    }

    /**
     * @return array<string, mixed>
     */
    public static function putSubjectProvider(): array
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

    #[DataProvider('putSubjectProvider')]
    public function testPutCreate(mixed $subject, mixed $expected): void
    {
        DotKey::on($subject)->put("a.q.n", 1);

        $this->assertEquals($expected, $subject);
    }

    public function testPutCreateDeep(): void
    {
        $subject = ['a' => ['q' => 1]];
        DotKey::on($subject)->put("a.b.c.d.e.f", 1);

        $expected = ['a' => ['q' => 1, 'b' => ['c' => ['d' => ['e' => ['f' => 1]]]]]];
        $this->assertEquals($expected, $subject);
    }

    #[DataProvider('putSubjectProvider')]
    public function testPutCreateForceAssoc(mixed $subject, mixed $_, mixed $expected): void
    {
        DotKey::on($subject)->put("a.q.n", 1, '.', true);

        $this->assertEquals($expected, $subject);
    }

    #[DataProvider('putSubjectProvider')]
    public function testPutCreateForceObject(mixed $subject, mixed $_, mixed $__, mixed $expected): void
    {
        DotKey::on($subject)->put("a.q.n", 1, '.', false);

        $this->assertEquals($expected, $subject);
    }

    #[DataProvider('privateProvider')]
    public function testPutPrivateProperty($subject, string $path, string $at): void
    {
        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to put '$path': error at '$at'");

        DotKey::on($subject)->put($path, 10);
    }

    /**
     * @return array<string, mixed>
     */
    public static function putOverwriteSubjectProvider(): array
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

    #[DataProvider('putOverwriteSubjectProvider')]
    public function testPutOverwriteExisting(mixed $subject, mixed $expected): void
    {
        DotKey::on($subject)->put("a.b.n", 1, '.');

        $this->assertEquals($expected, $subject);
    }

    public function testPutWithInvalidDelimiter(): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Delimiter can't be an empty string");

        DotKey::on($subject)->put('ab', 1, '');
    }

    #[DataProvider('putSubjectProvider')]
    public function testPutOnCopy(mixed $subject, mixed $expected): void
    {
        DotKey::onCopy($subject, $copy)->put("a.q.n", 1);

        $this->assertNotSame($subject, $copy);
        $this->assertEquals($expected, $copy);
    }

    public function testPunOnCopyReplace(): void
    {
        $subject = (object)['a' => (object)['b' => (object)['x' => 'y']]];
        DotKey::onCopy($subject, $copy)->put("a.b", 'foo');

        $this->assertNotSame($subject, $copy);
        $this->assertEquals((object)['a' => (object)['b' => (object)['x' => 'y']]], $subject);
        $this->assertEquals((object)['a' => (object)['b' => 'foo']], $copy);
    }

    public function testPunOnCopyReplaceCreate(): void
    {
        $subject = (object)['a' => (object)['b' => 'foo']];
        DotKey::onCopy($subject, $copy)->put("a.b.c", 1);

        $this->assertNotSame($subject, $copy);
        $this->assertEquals((object)['a' => (object)['b' => (object)['c' => 1]]], $copy);
    }

    public function testPutOnCopyNoChange(): void
    {
        $subject = (object)['a' => (object)['b' => 'foo']];
        DotKey::onCopy($subject, $copy)->put("a.b", 'foo');

        $this->assertSame($subject, $copy);
    }

    /**
     * @return array<string, mixed>
     */
    public static function removeSubjectProvider(): array
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

    #[DataProvider('removeSubjectProvider')]
    public function testRemove(mixed $subject, mixed $expected): void
    {
        DotKey::on($subject)->remove("a.b.n");

        $this->assertEquals($expected, $subject);
    }

    #[DataProvider('removeSubjectProvider')]
    public function testRemoveBlock(mixed $subject, mixed $_, mixed $expected): void
    {
        DotKey::on($subject)->remove("a.b");

        $this->assertEquals($expected, $subject);
    }

    #[DataProvider('pathProvider')]
    public function testRemoveWithDelimiter(string $delimiter, string $abx): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        DotKey::on($subject)->remove($abx, $delimiter);

        $this->assertEquals(['a' => ['b' => []]], $subject);
    }

    #[DataProvider('removeSubjectProvider')]
    public function testRemoveWithNonExistingPath(mixed $subject): void
    {
        DotKey::on($subject)->remove('a.r.d');

        $this->assertSame($subject, $subject);
    }

    #[DataProvider('pathProvider')]
    public function testRemoveWithUnresolvablePath(string $delimiter, string $basePath): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];
        $path = str_replace('x', 'x' . $delimiter . 'o1' . $delimiter . 'q11', $basePath);
        $invalidPath = rtrim($basePath, $delimiter);

        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to remove '$path': '$invalidPath' is of type string");

        DotKey::on($subject)->remove($path, $delimiter);
    }

    #[DataProvider('privateProvider')]
    public function testRemovePrivateProperty(mixed $subject, string $path, string $at): void
    {
        $this->expectException(ResolveException::class);
        $this->expectExceptionMessage("Unable to remove '$path': error at '$at'");

        DotKey::on($subject)->remove($path);
    }

    public function testRemoveWithInvalidDelimiter(): void
    {
        $subject = ['a' => ['b' => ['x' => 'y']]];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Delimiter can't be an empty string");

        DotKey::on($subject)->remove('ab', '');
    }

    #[DataProvider('removeSubjectProvider')]
    public function testRemoveOnCopy(mixed $subject, mixed $expected): void
    {
        DotKey::onCopy($subject, $copy)->remove('a.b.n');

        $this->assertNotSame($subject, $copy);
        $this->assertEquals($expected, $copy);
    }


    #[DataProvider('removeSubjectProvider')]
    public function testRemoveOnCopyNotExists(mixed $subject, mixed $_): void
    {
        DotKey::onCopy($subject, $copy)->remove('a.b.r');

        $this->assertSame($subject, $copy);
    }
}
