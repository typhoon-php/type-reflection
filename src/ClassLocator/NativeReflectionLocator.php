<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\FileResource;

/**
 * @api
 */
final class NativeReflectionLocator implements ClassLocator
{
    public function locateClass(string $name): null|FileResource|\ReflectionClass
    {
        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            return new \ReflectionClass($name);
        } catch (\ReflectionException) {
            return null;
        }
    }
}
