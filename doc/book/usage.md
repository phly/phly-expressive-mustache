# Installation and Usage

## Installation

Install via [composer](https://getcomposer.org):

```bash
$ composer require phly/phly-expressive-mustache
```

## Usage

You will need to register `Phly\Expressive\Mustache\MustacheTemplate` under the
service name `Zend\Expressive\Template\TemplateInterface`. You have two ways to
do this:

- Create your own factory for use with the [container-interop](https://github.com/container-interop/container-interop)
  interfaces and your selected implementation.
- Use the provided `Phly\Expressive\Mustache\MustacheTemplateFactory`. This will
  require you also provide a `Config` service, which returns an array containing
  the key `phly-mustache`, and which has valid configuration keys as detailed
  below.

### Configuring the factory

#### Using zend-servicemanager

To use the provided factory with [zend-servicemanager](https://github.com/zendframework/zend-servicemanager),
attach it using `setFactory()`:

```php
use Phly\Expressive\Mustache\MustacheTemplateFactory;
use Zend\Expressive\Template\TemplateInterface;

$container->setFactory(TemplateInterface::class, MustacheTemplateFactory::class);
```

Via configuration:

```php
[
    'factories' => [
        'Zend\Expressive\Template\TemplateInterface' => 'Phly\Expressive\Mustache\MustacheTemplateFactory',
    ],
],
```

#### Using Pimple (interop)

To use Pimple, assign an instance of the factory to the `TemplateInterface`
service:

```php
use Interop\Container\PimpleInterop as Pimple;
use Phly\Expressive\Mustache\MustacheTemplateFactory;
use Zend\Expressive\Template\TemplateInterface;

$pimple = new Pimple();
$pimple[TemplateInterface::class] = new MustacheTemplateFactory();
```

### Configuration

The following is the configuration schema for supplied
`MustacheTemplateFactory`.

```php
[
    'phly-mustache' => [
        'paths' => [
            // Either namespace => path pairs, or unnamed paths.
            // If no key is provided, the default namespace is assumed.
            // Paths may be individual strings, or arrays of strings, to allow
            // multiple paths for a given namespace:
            //
            // 'path-without/namespace/',
            // 'layout' => 'path/to/layouts/',
            // 'error' => [
            //     'first/path/for/error_templates',
            //     'second/path/for/error_templates',
            // ],
            // [
            //     'non-namespaced/path',
            //     'second/non-namespaced/path',
            // ],
        ],
        'suffix'    => '...', // Alternate template suffix to use.
        'separator' => '...', // Alternate directory separator character used in template names.
        'resolvers' => [
            // List of classes and/or service names referring to
            // Phly\Mustache\Resolver\ResolverInterface implementations to
            // inject in the aggregate composed by Mustache.
        ],
        'pragmas' => [
            // List of classes and/or service names referring to
            // Phly\Mustache\Pragma\PragmaInterface implementations to
            // inject into Mustache's pragma collection.
        ],
        'lexer'    => '...', // Service name of alternate lexer to use.
        'renderer' => '...', // Service name of alternate renderer to use.
        'escaper'  => '...', // Service name of alternate escaper to use with renderer.
        'disable_strip_whitespace' => true, // Flag for enabling/disabling
                                            // whitepace stripping in the lexer;
                                            // default is to enable it.
    ],
]
```

The factory will consume as much or as little of the above configuration that
you provide.
