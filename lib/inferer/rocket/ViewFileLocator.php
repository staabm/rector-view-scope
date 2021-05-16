<?php

namespace ViewScopeRector\Inferer\Rocket;

use PhpParser\Node\Expr\Variable;
use Symplify\SmartFileSystem\SmartFileInfo;

final class ViewFileLocator implements \ViewScopeRector\Inferer\Rocket\FileLocator
{
    /**
     * @var string
     */
    private $filePath;

    public function __construct(string $filePath) {
        // normalize path
        $this->filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
    }

    public function isInViewPath(): bool
    {
        return strpos($this->filePath, DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR) !== false;
    }

    public function isTopLevelView(): bool
    {
        if (!$this->isInViewPath()) {
            return false;
        }
        
        return strpos($this->filePath, DIRECTORY_SEPARATOR.'_') === false;
    }

    public function findMatchingController(): ?string
    {
        if (!$this->isTopLevelView()) {
            return null;
        }

        $viewRootName = DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR;
        $pos = strpos($this->filePath, $viewRootName);

        $viewPath = substr($this->filePath, $pos + strlen($viewRootName));
        $pos = strpos($viewPath, DIRECTORY_SEPARATOR);

        if ($pos) {
            $controllerName = substr($viewPath, 0, $pos);
            return '\\'.ucfirst($controllerName).'Controller';
        }

        return null;
    }
}
