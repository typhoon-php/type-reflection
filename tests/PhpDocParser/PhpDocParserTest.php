<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\PhpDocParser;

use ExtendedTypeSystem\Reflection\Variance;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser as PHPStanPhpDocParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\anything;
use function PHPUnit\Framework\never;

#[CoversClass(PhpDocParser::class)]
#[CoversClass(PhpDocBuilder::class)]
#[CoversClass(PhpDoc::class)]
final class PhpDocParserTest extends TestCase
{
    public function testNothingIsCalledForNodeWithEmptyPHPDoc(): void
    {
        $parser = $this->createMock(PHPStanPhpDocParser::class);
        $parser->expects(never())->method(anything());
        $lexer = $this->createMock(Lexer::class);
        $lexer->expects(never())->method(anything());
        $phpDocParser = new PhpDocParser(parser: $parser, lexer: $lexer);

        $phpDocParser->parsePhpDoc('');
    }

    public function testItReturnsNullVarTypeWhenNoVarTag(): void
    {
        $parser = new PhpDocParser();

        $varType = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->varType;

        self::assertNull($varType);
    }

    public function testItReturnsLatestPrioritizedVarTagType(): void
    {
        $parser = new PhpDocParser();

        $varType = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * @var int
                 * @psalm-var float
                 * @psalm-var string
                 */
                PHP,
        )->varType;

        self::assertEquals(new IdentifierTypeNode('string'), $varType);
    }

    public function testItReturnsNullParamTypeWhenNoParamTag(): void
    {
        $parser = new PhpDocParser();

        $paramTypes = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->paramTypes;

        self::assertEmpty($paramTypes);
    }

    public function testItReturnsLatestPrioritizedParamTagType(): void
    {
        $parser = new PhpDocParser();

        $paramTypes = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * @param int $a
                 * @param object $b
                 * @param mixed $b
                 * @psalm-param float $a
                 * @psalm-param string $a
                 */
                PHP,
        )->paramTypes;

        self::assertEquals(
            [
                'a' => new IdentifierTypeNode('string'),
                'b' => new IdentifierTypeNode('mixed'),
            ],
            $paramTypes,
        );
    }

    public function testItReturnsNullReturnTypeWhenNoReturnTag(): void
    {
        $parser = new PhpDocParser();

        $returnType = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->returnType;

        self::assertNull($returnType);
    }

    public function testItReturnsLatestPrioritizedReturnTagType(): void
    {
        $parser = new PhpDocParser();

        $returnType = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * @return int
                 * @psalm-return float
                 * @psalm-return string
                 */
                PHP,
        )->returnType;

        self::assertEquals(new IdentifierTypeNode('string'), $returnType);
    }

    public function testItReturnsEmptyTemplatesWhenNoTemplateTag(): void
    {
        $parser = new PhpDocParser();

        $templates = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->templates;

        self::assertEmpty($templates);
    }

    public function testItReturnsLatestPrioritizedTemplates(): void
    {
        $parser = new PhpDocParser();

        $templates = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * @template T of int
                 * @template T2 of object
                 * @template T2 of mixed
                 * @psalm-template T of float
                 * @psalm-template T of string
                 */
                PHP,
        )->templates;

        self::assertEquals(
            [
                'T' => $this->createTemplateTagValueNode('T', new IdentifierTypeNode('string'), Variance::INVARIANT),
                'T2' => $this->createTemplateTagValueNode('T2', new IdentifierTypeNode('mixed'), Variance::INVARIANT),
            ],
            $templates,
        );
    }

    public function testItAddsVarianceAttributeToTemplates(): void
    {
        $parser = new PhpDocParser();

        $templates = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @template TInvariant
                 * @template-covariant TCovariant
                 * @template-contravariant TContravariant
                 */
                PHP,
        )->templates;

        self::assertEquals(
            [
                'TInvariant' => $this->createTemplateTagValueNode('TInvariant', null, Variance::INVARIANT),
                'TCovariant' => $this->createTemplateTagValueNode('TCovariant', null, Variance::COVARIANT),
                'TContravariant' => $this->createTemplateTagValueNode('TContravariant', null, Variance::CONTRAVARIANT),
            ],
            $templates,
        );
    }

    public function testItReturnsEmptyInheritedTypesWhenNoExtendsTag(): void
    {
        $parser = new PhpDocParser();

        $inheritedTypes = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->extendedTypes;

        self::assertEmpty($inheritedTypes);
    }

    public function testItReturnsLatestPrioritizedExtendedTypes(): void
    {
        $parser = new PhpDocParser();

        $extendedTypes = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * 
                 * @extends C<int>
                 * @extends D<object>
                 * @extends D<mixed>
                 * @phpstan-extends C<float>
                 * @phpstan-extends C<string>
                 */
                PHP,
        )->extendedTypes;

        self::assertEquals(
            [
                $this->createGenericTypeNode(new IdentifierTypeNode('C'), [new IdentifierTypeNode('string')]),
                $this->createGenericTypeNode(new IdentifierTypeNode('D'), [new IdentifierTypeNode('mixed')]),
            ],
            $extendedTypes,
        );
    }

    public function testItReturnsEmptyInheritedTypesWhenNoImplementsTag(): void
    {
        $parser = new PhpDocParser();

        $inheritedTypes = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->implementedTypes;

        self::assertEmpty($inheritedTypes);
    }

    public function testItReturnsLatestPrioritizedImplementedTypes(): void
    {
        $parser = new PhpDocParser();

        $implemented = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * 
                 * @implements C<int>
                 * @implements D<object>
                 * @implements D<mixed>
                 * @phpstan-implements C<float>
                 * @phpstan-implements C<string>
                 */
                PHP,
        )->implementedTypes;

        self::assertEquals(
            [
                $this->createGenericTypeNode(new IdentifierTypeNode('C'), [new IdentifierTypeNode('string')]),
                $this->createGenericTypeNode(new IdentifierTypeNode('D'), [new IdentifierTypeNode('mixed')]),
            ],
            $implemented,
        );
    }

    public function testItCachesPriority(): void
    {
        $tagPrioritizer = $this->createMock(TagPrioritizer::class);
        $tagPrioritizer->expects(self::exactly(3))->method('priorityFor')->willReturn(0);
        $parser = new PhpDocParser(tagPrioritizer: $tagPrioritizer);

        $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @param string $a
                 * @param string $a
                 * @param string $a
                 */
                PHP,
        );
    }

    private function createTemplateTagValueNode(string $name, ?TypeNode $bound, Variance $variance): TemplateTagValueNode
    {
        $template = new TemplateTagValueNode($name, $bound, '');
        $template->setAttribute('variance', $variance);

        return $template;
    }

    /**
     * @param list<TypeNode> $genericTypes
     */
    private function createGenericTypeNode(IdentifierTypeNode $type, array $genericTypes): GenericTypeNode
    {
        return new GenericTypeNode(
            type: $type,
            genericTypes: $genericTypes,
            variances: array_fill(0, \count($genericTypes), GenericTypeNode::VARIANCE_INVARIANT),
        );
    }
}