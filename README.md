![jasny-banner](https://user-images.githubusercontent.com/100821/62123924-4c501c80-b2c9-11e9-9677-2ebc21d9b713.png)

Jasny DB
========

[![Build Status](https://secure.travis-ci.org/jasny/dotkey.png?branch=master)](http://travis-ci.org/jasny/dotkey)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/dotkey/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/dotkey/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/dotkey/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/dotkey/?branch=master)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/dotkey.svg)](https://packagist.org/packages/jasny/dotkey)
[![Packagist License](https://img.shields.io/packagist/l/jasny/dotkey.svg)](https://packagist.org/packages/jasny/dotkey)

Access objects and arrays through dot notation.

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
DotKey::on($subject)->get("a.b.x.o");         // Throws ResolveException

DotKey::on($subject)->set("a.b.q", "foo");    // ["a" => ["b" => ["x" => "y", "q" => "foo"]]] 
DotKey::on($subject)->set("a.d", ['p' => 1]); // ["a" => ["b" => ["x" => "y"]], "d" => ["p" => 1]]
DotKey::on($subject)->set("a.c.x", "bar");    // Throws ResolveException
DotKey::on($subject)->set("a.b.x.o", "qux");  // Throws ResolveException

DotKey::on($subject)->put("a.b.q", "foo");    // ["a" => ["b" => ["x" => "y"]], "d" => ["p" => 1]]
DotKey::on($subject)->put("a.c.x", "bar");    // ["a" => ["b" => ["x" => "y"], "c" => "bar"]]] 
DotKey::on($subject)->put("a.b.x.o", "qux");  // ["a" => ["b" => ["x" => ["o" => "qux"]]]]

DotKey::on($subject)->remove("a.b.x");
DotKey::on($subject)->remove("a.c.z");
DotKey::on($subject)->remove("a.b.x.o");      // Throws ResolveException
DotKey::on($subject)->remove("a.b");
```

The subject may be an array or object. Objects will be modified. They are not cloned.

```php
use Jasny\DotKey\DotKey;

$obj = (object)$subject;

DotKey::on($obj)->exists("a.b.x");
DotKey::on($obj)->set("a.b.q", "foo");

$obj; // (object)["a" => ["b" => ["x" => "y", "q" => "foo"]]] 
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
subject. Passing `true` as fourth argument forces this to an array, while passing `false` forces it to creating an
object.

```php
use Jasny\DotKey\DotKey;

$subject = ["a" => []];

DotKey::on($subject)->put("a.b.o", 1, '.');                // ["a" => ["b" => ["o" => 1]]]
DotKey::on($subject)->put("a.b.o", 1, '.', true);          // ["a" => ["b" => ["o" => 1]]]
DotKey::on($subject)->put("a.b.o", 1, '.', false);         // ["a" => (object)["b" => (object)["o" => 1]]]

DotKey::on((object)$subject)->put("a.b.o", 1, '.');        // (object)["a" => (object)["b" => (object)["o" => 1]]]
DotKey::on((object)$subject)->put("a.b.o", 1, '.', true);  // (object)["a" => ["b" => ["o" => 1]]]
DotKey::on((object)$subject)->put("a.b.o", 1, '.', false); // (object)["a" => (object)["b" => (object)["o" => 1]]]
```
