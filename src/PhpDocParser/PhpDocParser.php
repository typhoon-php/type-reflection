<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\PhpDocParser;

use PhpParser\Node;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser as PHPStanPhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
 */
final class PhpDocParser
{
    public function __construct(
        private readonly TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer(),
        private readonly PHPStanPhpDocParser $parser = new PHPStanPhpDocParser(
            typeParser: new TypeParser(new ConstExprParser()),
            constantExprParser: new ConstExprParser(),
            requireWhitespaceBeforeDescription: true,
        ),
        private readonly Lexer $lexer = new Lexer(),
    ) {
    }

    public function parsePhpDoc(string $phpDoc): PhpDoc
    {
        if (!str_contains($phpDoc, '@')) {
            return new PhpDoc();
        }

        $tokens = $this->lexer->tokenize($phpDoc);
        $tags = $this->parser->parse(new TokenIterator($tokens))->getTags();

        return (new PhpDocBuilder($this->tagPrioritizer))
            ->addTags($tags)
            ->build();
    }

    public function parseNodePhpDoc(Node $node): PhpDoc
    {
        $phpDoc = $node->getAttribute(PhpDoc::class);

        if ($phpDoc instanceof PhpDoc) {
            return $phpDoc;
        }

        $phpDoc = $this->parsePhpDoc($node->getDocComment()?->getText() ?? '');
        $node->setAttribute(PhpDoc::class, $phpDoc);

        return $phpDoc;
    }
}