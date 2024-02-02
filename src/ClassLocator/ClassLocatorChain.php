<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class ClassLocatorChain implements ClassLocator
{
    /**
     * @param iterable<ClassLocator> $classLocators
     */
    public function __construct(
        private readonly iterable $classLocators,
    ) {}

    public function locateClass(string $name): null|Resource|\ReflectionClass
    {
        foreach ($this->classLocators as $classLocator) {
            $file = $classLocator->locateClass($name);

            if ($file !== null) {
                return $file;
            }
        }

        return null;
    }
}
