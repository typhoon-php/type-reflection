<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\Reflection\Internal\ClassKind;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\ReflectionHook;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class EnsureReadonlyClassPropertiesAreReadonly implements ReflectionHook
{
    public function reflect(FunctionId|ClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        if ($id instanceof FunctionId) {
            return $data;
        }

        if ($data[Data::ClassKind()] !== ClassKind::Class_) {
            return $data;
        }

        if ($data[Data::NativeReadonly()]) {
            $data = $data->set(Data::Properties(), array_map(
                static fn(TypedMap $property): TypedMap => $property->set(Data::NativeReadonly(), true),
                $data[Data::Properties()] ?? [],
            ));
        }

        if ($data[Data::AnnotatedReadonly()] ?? false) {
            $data = $data->set(Data::Properties(), array_map(
                static fn(TypedMap $property): TypedMap => $property->set(Data::AnnotatedReadonly(), true),
                $data[Data::Properties()] ?? [],
            ));
        }

        return $data;
    }
}
