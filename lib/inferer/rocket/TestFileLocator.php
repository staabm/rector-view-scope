<?php

namespace ViewScopeRector\Inferer\Rocket;

use PhpParser\Node\Expr\Variable;

final class TestFileLocator implements FileLocator
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
        return true;
    }

    public function isTopLevelView(): bool
    {
        return true;
    }

    public function findMatchingController(): ?string
    {
        $variable = $this->variable;

        if ($variable->name == "myspecialtest") {
            return '\AdmgrpController';
        }

        if ($variable->name != "hansipansi-nowhere-used-xxx") {
            return '\IndexController';
        }
        return null;
    }
}
