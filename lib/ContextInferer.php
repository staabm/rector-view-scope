<?php

namespace ViewScopeRector;

use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

interface ContextInferer
{
    /**
     * Tries to to infer the type for the given variable.
     * Returns null, when the inferer doesn't know a type of the given node.
     */
    public function infer(Node\Expr\Variable $variable): ?TypeNode;
}