<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeVisitorAbstract;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\Reflection\Internal\DeclarationIdMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class FindTopLevelDeclarationsVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     * @var DeclarationIdMap<ClassId|AnonymousClassId, ClassLike>
     */
    public DeclarationIdMap $nodes;

    public function __construct()
    {
        /** @var DeclarationIdMap<ClassId|AnonymousClassId, ClassLike> */
        $this->nodes = new DeclarationIdMap();
    }

    public function beforeTraverse(array $nodes): ?array
    {
        /** @var DeclarationIdMap<ClassId|AnonymousClassId, ClassLike> */
        $this->nodes = new DeclarationIdMap();

        return null;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof ClassLike) {
            $id = SetTypeContextVisitor::getNodeTypeContext($node)->id;
            \assert($id instanceof ClassId || $id instanceof AnonymousClassId);
            $this->nodes = $this->nodes->with($id, $node);

            return null;
        }

        return null;
    }
}