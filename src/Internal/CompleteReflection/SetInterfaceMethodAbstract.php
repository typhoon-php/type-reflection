<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\ClassKind;
use Typhoon\Reflection\Internal\Hook\ClassHook;
use Typhoon\Reflection\Internal\Hook\HookPriorities;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum SetInterfaceMethodAbstract implements ClassHook
{
    case Instance;

    public function priority(): int
    {
        return HookPriorities::COMPLETE_REFLECTION;
    }

    public function processClass(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        if ($data[Data::ClassKind] !== ClassKind::Interface) {
            return $data;
        }

        return $data->with(Data::Methods, array_map(
            static fn(TypedMap $method): TypedMap => $method->with(Data::Abstract, true),
            $data[Data::Methods],
        ));
    }
}
