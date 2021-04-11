<?php

namespace ViewScopeRector;

use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @phpstan-template T of Node
 */
interface ContextInferer {
    /**
     * Infers the type for the given node
     *
     * @param T $variable
     * @return TypeNode|null
     */
    public function infer(Node $variable): ?TypeNode;
}