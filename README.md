# Typhoon Reflection

This library is an alternative to [native PHP Reflection](https://www.php.net/manual/en/book.reflection.php). It is:
- static,
- lazy,
- cacheable,
- compatible with native reflection,
- supports most of the Psalm/PHPStan types,
- can resolve templates,
- does not create circular object references, can be safely used with `zend.enable_gc=0`.

## Installation

```
composer require typhoon/reflection jetbrains/phpstorm-stubs
```

Installing `jetbrains/phpstorm-stubs` is highly recommended.
Without stubs native PHP classes are reflected via native reflector that does not support templates. 

## Basic Usage

```php
namespace My\Awesome\App;

use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Type\types;

/**
 * @template T
 */
final readonly class Article
{
    /**
     * @param non-empty-list<non-empty-string> $tags
     * @param T $data
     */
    public function __construct(
        private array $tags,
        public mixed $data,
    ) {}
}

$reflector = TyphoonReflector::build();
$articleReflection = $reflector->reflectClass(Article::class);

$tagsReflection = $articleReflection->getProperty('tags');

var_dump($tagsReflection->getTyphoonType()); // object representation of non-empty-list<non-empty-string> type

$dataReflection = $articleReflection->getProperty('data');

var_dump($dataReflection->getTyphoonType()); // object representation of T template type
```

## Compatibility

This library is 99% compatible with native reflection API. See [compatibility](docs/compatibility.md) and [ReflectorCompatibilityTest](tests/unit/ReflectorCompatibilityTest.php) for more details.

## Caching

The recommended way to cache reflection is using [Typhoon OPcache](https://github.com/typhoon-php/opcache).

```php
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\OPcache\TyphoonOPcache;

$reflector = TyphoonReflector::build(
    cache: new TyphoonOPcache('path/to/cache/dir'),
);
```

To detect file changes during development, decorate cache with [ChangeDetectingCache](src/Cache/ChangeDetectingCache.php).

```php
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Reflection\Cache\ChangeDetectingCache;
use Typhoon\OPcache\TyphoonOPcache;

$reflector = TyphoonReflector::build(
    cache: new ChangeDetectingCache(new TyphoonOPcache('path/to/cache/dir')),
);
```

## Class locators

By default, reflector uses:
- [ComposerClassLocator](src/ClassLocator/ComposerClassLocator.php) if composer autoloading is used, 
- [PhpStormStubsClassLocator](src/ClassLocator/PhpStormStubsClassLocator.php) if `jetbrains/phpstorm-stubs` is installed,
- [NativeReflectionFileLocator](src/ClassLocator/NativeReflectionFileLocator.php) (tries to detect class file via native reflection),
- [NativeReflectionLocator](src/ClassLocator/NativeReflectionLocator.php) (returns native reflection).

You can implement your own locators and pass them to the `build` method:

```php
use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\TyphoonReflector;

final class MyClassLocator implements ClassLocator
{
    // ...
}

$reflector = TyphoonReflector::build(
    classLocators: [
        new MyClassLocator(),
        ...TyphoonReflector::defaultClassLocators(),
    ],
);
```

## TODO

- [ ] traits
- [ ] class constants
- [ ] functions
