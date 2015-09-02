# phly-expressive-mustache

[![Build Status](https://secure.travis-ci.org/phly/phly-expressive-mustache.png?branch=develop)](http://travis-ci.org/phly/phly-expressive-mustache)

phly-expressive-mustache provides a [zend-expressive](http://zend-expressive.rtfd.org)
`TemplateInterface` adapter for [phly-mustache](https://github.com/phly/phly-mustache).

## Installation

Install via composer:

```bash
$ composer require phly/phly-expressive-mustache
```

## Documentation

You can build documentation in one of two ways:

- [MkDocs](http://www.mkdocs.org): Execute `mkdocs build` from the repository
  root.
- [Bookdown](http://bookdown.io): Execute `bookdown doc/bookdown.json` from the
  repository root.

In each case, you can use PHP's built-in web server to serve the documentation:

```bash
$ php -S 0.0.0.0:8080 -t doc/html/
```

and then browse to http://localhost:8080/.

## Usage

See the [manual](doc/book/usage.md) for usage.
