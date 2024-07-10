<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @extends DefaultTypeVisitor<\ReflectionType>
 * @todo check array is array<array-key, mixed>?
 */
final class ToNativeTypeConverter extends DefaultTypeVisitor
{
    public function never(Type $type): mixed
    {
        return NamedTypeAdapter::never();
    }

    public function void(Type $type): mixed
    {
        return NamedTypeAdapter::void();
    }

    public function null(Type $type): mixed
    {
        return NamedTypeAdapter::null();
    }

    public function true(Type $type): mixed
    {
        return NamedTypeAdapter::true();
    }

    public function false(Type $type): mixed
    {
        return NamedTypeAdapter::false();
    }

    public function int(Type $type, ?int $min, ?int $max): mixed
    {
        if ($min === null && $max === null) {
            return NamedTypeAdapter::int();
        }

        throw new NonConvertableType($type);
    }

    public function float(Type $type): mixed
    {
        return NamedTypeAdapter::float();
    }

    public function string(Type $type): mixed
    {
        return NamedTypeAdapter::string();
    }

    public function array(Type $type, Type $keyType, Type $valueType, array $elements): mixed
    {
        return NamedTypeAdapter::array();
    }

    public function iterable(Type $type, Type $keyType, Type $valueType): mixed
    {
        return NamedTypeAdapter::iterable();
    }

    public function object(Type $type, array $properties): mixed
    {
        return NamedTypeAdapter::object();
    }

    public function namedObject(Type $type, NamedClassId $class, array $typeArguments): mixed
    {
        return NamedTypeAdapter::namedObject($class->name);
    }

    public function self(Type $type, ?ClassId $resolvedClass, array $typeArguments): mixed
    {
        return NamedTypeAdapter::namedObject('self');
    }

    public function parent(Type $type, ?NamedClassId $resolvedClass, array $typeArguments): mixed
    {
        return NamedTypeAdapter::namedObject('parent');
    }

    public function static(Type $type, ?ClassId $resolvedClass, array $typeArguments): mixed
    {
        return NamedTypeAdapter::namedObject('static');
    }

    public function callable(Type $type, array $parameters, Type $returnType): mixed
    {
        return NamedTypeAdapter::callable();
    }

    public function union(Type $type, array $ofTypes): mixed
    {
        // TODO use comparator
        if ($type === types::bool) {
            return NamedTypeAdapter::bool();
        }

        $convertedTypes = [];
        $hasNull = false;

        foreach ($ofTypes as $ofType) {
            $convertedType = $ofType->accept($this);

            if (!$convertedType instanceof \ReflectionNamedType && !$convertedType instanceof \ReflectionIntersectionType) {
                throw new NonConvertableType($type);
            }

            if ($convertedType instanceof \ReflectionNamedType && $convertedType->getName() === 'null') {
                $hasNull = true;

                continue;
            }

            $convertedTypes[] = $convertedType;
        }

        if ($hasNull) {
            if (\count($convertedTypes) === 1 && $convertedTypes[0] instanceof NamedTypeAdapter) {
                return $convertedTypes[0]->toNullable();
            }

            $convertedTypes[] = NamedTypeAdapter::null();
        }

        \assert(\count($convertedTypes) > 1);

        return new UnionTypeAdapter($convertedTypes);
    }

    public function intersection(Type $type, array $ofTypes): mixed
    {
        return new IntersectionTypeAdapter(array_map(
            function (Type $ofType) use ($type): \ReflectionNamedType {
                $converted = $ofType->accept($this);

                if ($converted instanceof \ReflectionNamedType) {
                    return $converted;
                }

                throw new NonConvertableType($type);
            },
            $ofTypes,
        ));
    }

    public function mixed(Type $type): mixed
    {
        return NamedTypeAdapter::mixed();
    }

    protected function default(Type $type): mixed
    {
        throw new NonConvertableType($type);
    }
}
