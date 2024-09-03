![jasny-banner](https://user-images.githubusercontent.com/100821/62123924-4c501c80-b2c9-11e9-9677-2ebc21d9b713.png)

DotKey
===

[![PHP](https://github.com/jasny/dotkey/actions/workflows/php.yml/badge.svg)](https://github.com/jasny/dotkey/actions/workflows/php.yml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/dotkey/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/dotkey/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/dotkey/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/dotkey/?branch=master)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/dotkey.svg)](https://packagist.org/packages/jasny/dotkey)
[![Packagist License](https://img.shields.io/packagist/l/jasny/dotkey.svg)](https://packagist.org/packages/jasny/dotkey)

Dot notation access for objects and arrays.

Inspired by the [node.js Dotty](https://github.com/deoxxa/dotty) library.


Installation
---

    composer require jasny/dotkey

Usage
---

```php
use Jasny\DotKey\DotKey;

$subject = [
  "a" => [
    "b" => [
      "x" => "y"
    ]
  ]
];

DotKey::on($subject)->exists("a.b.x");        // true
DotKey::on($subject)->exists("a.b.z");        // false
DotKey::on($subject)->exists("a.b.x.o");      // false

DotKey::on($subject)->get("a.b.x");           // "y"
DotKey::on($subject)->get("a.b");             // ["x" => "y"]
DotKey::on($subject)->get("a.b.z");           // null
DotKey::on($subject)->get("a.b.x.o");         // Throws ResolveException because a.b.x is a string

DotKey::on($subject)->set("a.b.q", "foo");    // $subject = ["a" => ["b" => ["x" => "y", "q" => "foo"]]]
DotKey::on($subject)->set("a.d", ['p' => 1]); // $subject = ["a" => ["b" => ["x" => "y"]], "d" => ["p" => 1]]
DotKey::on($subject)->set("a.c.x", "bar");    // Throws ResolveException because a.c doesn't exist
DotKey::on($subject)->set("a.b.x.o", "qux");  // Throws ResolveException because a.b.x is a string

DotKey::on($subject)->put("a.b.q", "foo");    // $subject = ["a" => ["b" => ["x" => "y", "q" => "foo"]]]
DotKey::on($subject)->put("a.c.x", "bar");    // $subject = ["a" => ["b" => ["x" => "y"]], "c" => ["x" => "bar"]]

DotKey::on($subject)->remove("a.b.x");        // $subject = ["a" => ["b" => []]]
DotKey::on($subject)->remove("a.c.z");        // $subject isn't modified
DotKey::on($subject)->remove("a.b.x.o");      // Throws ResolveException because a.b.x is a string
DotKey::on($subject)->remove("a.b");          // $subject = ["a" => []]

DotKey::on($subject)->update("a.b", fn($value) => array_map('strtoupper', $value)); // $subject = ["a" => ["b" => ["x" => "Y"]]]
```

The subject may be an array or object. If an object implements `ArrayAccess` it will be treated as array.

```php
use Jasny\DotKey\DotKey;

$obj = (object)["a" => (object)["b" => (object)["x" => "y"]]];

DotKey::on($obj)->exists("a.b.x");
DotKey::on($obj)->set("a.b.q", "foo");
```

`exists()` will return `false` when trying access a private or static property. All other methods will throw a
`ResolveException`.

### Copy

By default, the target is modified in place. With the `onCopy()` factory method, a copy will be made instead.

```php
use Jasny\DotKey\DotKey;

$source = ["a" => ["b" => ["x" => "y"]]];

DotKey::onCopy($source, $copy)->set("a.b.q", "foo");  // $copy = ["a" => ["b" => ["x" => "y", "q" => "foo"]]]
```

With `onCopy()`, objects will be cloned if they're modified.

```php
use Jasny\DotKey\DotKey;

$source = (object)["f" => (object)["x" => "y"], "g" => (object)["x" => "z"]]]];

DotKey::onCopy($source, $copy)->set("f.q", "foo");

$copy === $source;       // false, source is cloned
$copy->f === $source->f; // false, `f` is cloned and modified
$copy->g === $source->g; // true, `g` is not cloned
```

### Delimiter

The default delimiter is a dot, but any string can be used as delimiter. Leading and trailing delimiters are stripped.

```php
use Jasny\DotKey\DotKey;

DotKey::on($subject)->exists('/a/b/x', '/');
DotKey::on($subject)->get("/a/b/x", '/');
DotKey::on($subject)->set("/a/b/q", "foo", '/');
DotKey::on($subject)->put("/a/b/q", "foo", '/');
DotKey::on($subject)->remove("/a/b/q", '/');

DotKey::on($subject)->exists('a::b::c', '::');
```

### Set vs Put

* `set('a.b.c', 1)` requires `'a.b'` to exist and be an object or array. If that's not the case, a `ResolveException` is
thrown.
* `put('a.b.c', 1)` will always result in `$result["a"]["b"]["c"] === 1`. If `'a.b'` doesn't exist, it will be created.
If it already exists and isn't an array or object, it will be overwritten.

The structure `put()` creates is either an associative array or an `\stdClass` object, depending on the type of the
subject. Passing `true` as fourth argument forces this to an array, while passing `false` forces it to create an
object.

```php
use Jasny\DotKey\DotKey;

$subject = ["a" => null];
$obj = (object)["a" => null];

DotKey::on($subject)->put("a.b.o", 1, '.');        // ["a" => ["b" => ["o" => 1]]]
DotKey::on($subject)->put("a.b.o", 1, '.', true);  // ["a" => ["b" => ["o" => 1]]]
DotKey::on($subject)->put("a.b.o", 1, '.', false); // ["a" => (object)["b" => (object)["o" => 1]]]

DotKey::on($obj)->put("a.b.o", 1, '.');            // (object)["a" => (object)["b" => (object)["o" => 1]]]
DotKey::on($obj)->put("a.b.o", 1, '.', true);      // (object)["a" => ["b" => ["o" => 1]]]
DotKey::on($obj)->put("a.b.o", 1, '.', false);     // (object)["a" => (object)["b" => (object)["o" => 1]]]
```
