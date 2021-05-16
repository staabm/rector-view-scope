<?php

use PhpParser\Node\Expr\Variable;

final class TestFileLocator implements \ViewScopeRector\Inferer\Rocket\FileLocator
{
    /**
     * @var Variable
     */
    private $variable;

    public function __construct(Variable $variable) {
        $this->variable = $variable;
    }

    public function isInViewPath(): bool
    {
        // TODO implement me
        return true;
    }

    public function isTopLevelView(): bool
    {
        // TODO implement me
        return true;
    }

    public function findMatchingController(): ?string
    {
        $variable = $this->variable;

        // TODO implement me
        if ($variable->name == "myspecialtest") {
            return '\AdmgrpController';
        }

        if ($variable->name != "hansipansi-nowhere-used-xxx") {
            return '\IndexController';
        }
        return null;
    }
}
