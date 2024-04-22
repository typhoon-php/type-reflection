<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Expression;

use Typhoon\Reflection\Internal\ClassReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ArrayExpression implements Expression
{
    /**
     * @param non-empty-list<ArrayElement> $elements
     */
    public function __construct(
        private readonly array $elements,
    ) {}

    public function evaluate(ClassReflector $classReflector): mixed
    {
        $array = [];

        foreach ($this->elements as $element) {
            $value = $element->value->evaluate($classReflector);

            if ($element->key === null) {
                $array[] = $value;

                continue;
            }

            if ($element->key === true) {
                /** @psalm-suppress InvalidOperand */
                $array = [...$array, ...$value];

                continue;
            }

            /** @psalm-suppress MixedArrayOffset */
            $array[$element->key->evaluate($classReflector)] = $value;
        }

        return $array;
    }
}
