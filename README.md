DotKey
======

[![Build Status](https://travis-ci.org/jasny/dotkey.svg?branch=master)](https://travis-ci.org/jasny/dotkey)
[![Coverage Status](https://coveralls.io/repos/jasny/dotkey/badge.svg?branch=master&service=github)](https://coveralls.io/github/jasny/dotkey?branch=master)

Access objects and arrays through dot notation.

Inspired by the [node.js Dotty](https://github.com/deoxxa/dotty) library.


## Installation

    composer require jasny/dotkey

## Usage

```php
$data = (object)[
  a: [
    b: [
      "x" => "y"
    ]
  ]
];

DotKey::on($data)->exists("a.b.x");        // true
DotKey::on($data)->exists("a.b.z");        // false
DotKey::on($data)->exists("a.b.x.o");      // false

DotKey::on($data)->get("a.b.x");           // "y"
DotKey::on($data)->get("a.b");             // ["x" => "y"]
DotKey::on($data)->get("a.b.z");           // null
DotKey::on($data)->get("a.b.x.o");         // Triggers a warning

DotKey::on($data)->set("a.b.q", "foo");
DotKey::on($data)->set("a.d", ['p' => 1]);
DotKey::on($data)->set("a.c.x", "bar");    // Triggers a warning
DotKey::on($data)->set("a.b.x.o", "qux");  // Triggers a warning

DotKey::on($data)->put("a.b.q", "foo");
DotKey::on($data)->put("a.c.x", "bar");
DotKey::on($data)->put("a.b.x.o", "qux");  // Triggers a warning

DotKey::on($data)->remove("a.b.x");
DotKey::on($data)->remove("a.c.z");
DotKey::on($data)->remove("a.b.x.o");      // Triggers a warning
DotKey::on($data)->remove("a.b");
```
