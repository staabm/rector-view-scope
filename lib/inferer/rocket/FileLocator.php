<?php

namespace ViewScopeRector\Inferer\Rocket;

interface FileLocator
{
    public function isInViewPath(): bool;

    public function isTopLevelView(): bool;

    public function findMatchingController(): ?string;
}