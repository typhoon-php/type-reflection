<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Reflector\FriendlyReflection;
use Typhoon\Type\TypeVisitor;

/**
 * @api
 */
final class PropertyReflection extends FriendlyReflection
{
    public const IS_PUBLIC = \ReflectionProperty::IS_PUBLIC;
    public const IS_PROTECTED = \ReflectionProperty::IS_PROTECTED;
    public const IS_PRIVATE = \ReflectionProperty::IS_PRIVATE;
    public const IS_STATIC = \ReflectionProperty::IS_STATIC;
    public const IS_READONLY = \ReflectionProperty::IS_READONLY;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param non-empty-string $name
     * @param ?non-empty-string $docComment
     * @param int-mask-of<self::IS_*> $modifiers
     * @param class-string $class
     * @param ?positive-int $startLine
     * @param ?positive-int $endLine
     */
    public function __construct(
        public readonly string $name,
        public readonly string $class,
        private readonly ?string $docComment,
        private readonly bool $hasDefaultValue,
        private readonly bool $promoted,
        private readonly int $modifiers,
        private readonly bool $deprecated,
        /** @readonly */
        private TypeReflection $type,
        private readonly ?int $startLine,
        private readonly ?int $endLine,
        private ?\ReflectionProperty $nativeReflection = null,
    ) {}

    public function getDefaultValue(): mixed
    {
        return $this->getNativeReflection()->getDefaultValue();
    }

    /**
     * @return ?non-empty-string
     */
    public function getDocComment(): ?string
    {
        return $this->docComment;
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): TypeReflection
    {
        return $this->type;
    }

    public function getValue(?object $object = null): mixed
    {
        return $this->getNativeReflection()->getValue($object);
    }

    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }

    public function isInitialized(?object $object = null): bool
    {
        /** @var bool */
        return $this->getNativeReflection()->isInitialized($object);
    }

    /**
     * @return int-mask-of<self::IS_*>
     */
    public function getModifiers(): int
    {
        return $this->modifiers;
    }

    public function isStatic(): bool
    {
        return ($this->modifiers & self::IS_STATIC) !== 0;
    }

    public function isPublic(): bool
    {
        return ($this->modifiers & self::IS_PUBLIC) === \ReflectionProperty::IS_PUBLIC;
    }

    public function isProtected(): bool
    {
        return ($this->modifiers & self::IS_PROTECTED) !== 0;
    }

    public function isPrivate(): bool
    {
        return ($this->modifiers & self::IS_PRIVATE) !== 0;
    }

    public function isReadOnly(): bool
    {
        return ($this->modifiers & self::IS_READONLY) !== 0;
    }

    public function isPromoted(): bool
    {
        return $this->promoted;
    }

    public function isDeprecated(): bool
    {
        return $this->deprecated;
    }

    public function setValue(?object $object, mixed $value): void
    {
        if ($this->isStatic()) {
            $this->getNativeReflection()->setValue($value);
        } else {
            $this->getNativeReflection()->setValue($object, $value);
        }
    }

    /**
     * @return ?positive-int
     */
    public function getStartLine(): ?int
    {
        return $this->startLine;
    }

    /**
     * @return ?positive-int
     */
    public function getEndLine(): ?int
    {
        return $this->endLine;
    }

    public function __serialize(): array
    {
        $data = get_object_vars($this);
        unset($data['nativeReflection']);

        return $data;
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $name => $value) {
            $this->{$name} = $value;
        }
    }

    public function __clone()
    {
        if ((debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? null) !== self::class) {
            throw new ReflectionException();
        }
    }

    public function getNativeReflection(): \ReflectionProperty
    {
        return $this->nativeReflection ??= new \ReflectionProperty($this->class, $this->name);
    }

    protected function withResolvedTypes(TypeVisitor $typeResolver): static
    {
        $property = clone $this;
        $property->type = $this->type->withResolvedTypes($typeResolver);

        return $property;
    }

    protected function toChildOf(FriendlyReflection $parent): static
    {
        $property = clone $this;
        $property->type = $this->type->toChildOf($parent->type);

        return $property;
    }
}
