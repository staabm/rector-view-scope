<?php

namespace ViewScopeRector;

use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @phpstan-template T of Node
 */
interface ContextInferer
{
    /**
     * Tries to to infer the type for the given node.
     * Returns null, when the inferer doesn't know a type of the given node.
     *
     * @param T $variable
     * @return TypeNode|null
     */
    public function infer(Node $variable): ?TypeNode;
}