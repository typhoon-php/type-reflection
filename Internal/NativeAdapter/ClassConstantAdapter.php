<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\Reflection\ClassConstantReflection;
use Typhoon\Reflection\Kind;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @property-read non-empty-string $name
 * @property-read class-string $class
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ClassConstantAdapter extends \ReflectionClassConstant
{
    private bool $nativeLoaded = false;

    public function __construct(
        private readonly ClassConstantReflection $reflection,
    ) {
        unset($this->name, $this->class);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __get(string $name)
    {
        return match ($name) {
            'name' => $this->reflection->name,
            'class' => $this->getDeclaringClass()->name,
            default => new \LogicException(sprintf('Undefined property %s::$%s', self::class, $name)),
        };
    }

    public function __isset(string $name): bool
    {
        return $name === 'name' || $name === 'class';
    }

    public function __toString(): string
    {
        $this->loadNative();

        return parent::__toString();
    }

    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        return AttributeAdapter::from($this->reflection->attributes(), $name, $flags);
    }

    public function getDeclaringClass(): \ReflectionClass
    {
        $declaringClass = $this->reflection->declaringClass();

        if ($declaringClass->isTrait()) {
            return $this->reflection->class()->toNative();
        }

        return $declaringClass->toNative();
    }

    public function getDocComment(): string|false
    {
        return $this->reflection->phpDoc() ?? false;
    }

    public function getModifiers(): int
    {
        return ($this->isPublic() ? self::IS_PUBLIC : 0)
            | ($this->isProtected() ? self::IS_PROTECTED : 0)
            | ($this->isPrivate() ? self::IS_PRIVATE : 0)
            | ($this->isFinal() ? self::IS_FINAL : 0);
    }

    public function getName(): string
    {
        return $this->reflection->name;
    }

    public function getType(): ?\ReflectionType
    {
        if ($this->isEnumCase()) {
            return null;
        }

        return $this->reflection->type(Kind::Native)?->accept(new ToNativeTypeConverter());
    }

    public function getValue(): mixed
    {
        return $this->reflection->value();
    }

    public function hasType(): bool
    {
        return !$this->isEnumCase()
            && $this->reflection->type(Kind::Native) !== null;
    }

    public function isEnumCase(): bool
    {
        return $this->reflection->isEnumCase();
    }

    public function isFinal(): bool
    {
        return $this->reflection->isFinal(Kind::Native);
    }

    public function isPrivate(): bool
    {
        return $this->reflection->isPrivate();
    }

    public function isProtected(): bool
    {
        return $this->reflection->isProtected();
    }

    public function isPublic(): bool
    {
        return $this->reflection->isPublic();
    }

    private function loadNative(): void
    {
        if (!$this->nativeLoaded) {
            /** @psalm-suppress ArgumentTypeCoercion */
            parent::__construct($this->reflection->id->class->name, $this->name);
            $this->nativeLoaded = true;
        }
    }
}