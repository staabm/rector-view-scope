<?php

namespace ViewScopeRector\Inferer;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Reflection\DirectClassReflectionExtensionRegistryProvider;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Core\Provider\CurrentFileProvider;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Symplify\SmartFileSystem\SmartFileInfo;
use ViewScopeRector\ContextInferer;

/**
 * Implements view-variable type inferring for view-scripts in the Rocket-Framework (close-source) context.
 */
final class RocketViewContextInferer implements ContextInferer
{
    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;
    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;
    /**
     * @var StaticTypeMapper
     */
    private $staticTypeMapper;

    /**
     * @var SmartFileInfo
     */
    private $file;

    public function __construct(ReflectionProvider $reflectionProvider, NodeNameResolver $nodeNameResolver, StaticTypeMapper $staticTypeMapper, SmartFileInfo $file)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->nodeNameResolver = $nodeNameResolver;
        $this->staticTypeMapper = $staticTypeMapper;
        $this->file = $file;
    }

    public function infer(Variable $variable): ?TypeNode
    {
        if (!$this->isInViewPath()) {
            return null;
        }

        if (!$this->isTopLevelView()) {
            return null;
        }

        $controllerClass = $this->findMatchingController($variable);
        if (!$controllerClass) {
            return null;
        }

        return $this->inferTypeFromController($controllerClass, $variable);
    }

    private function isInViewPath(): bool
    {
        return strpos($this->file->getRealPath(), DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR) !== false;
    }

    private function isTopLevelView(): bool
    {
        return strpos($this->file->getRealPath(), DIRECTORY_SEPARATOR.'_') === false;
    }

    private function findMatchingController(Variable $variable): ?string
    {
        // TODO implement me
        if ($variable->name == "myspecialtest") {
            return '\AdmgrpController';
        }

        if ($variable->name != "hansipansi-nowhere-used-xxx") {
            return '\IndexController';
        }
        return null;
    }

    private function inferTypeFromController(string $controllerClass, Variable $node): ?TypeNode
    {
        /** @var Scope|null $scope */
        $scope = $node->getAttribute(AttributeKey::SCOPE);
        if ($scope === null) {
            throw new \RuntimeException("should not happen");
        }

        $propertyName = $this->nodeNameResolver->getName($node);
        if ($propertyName === null) {
            throw new \RuntimeException("should not happen");
        }

        // XXX ondrey hinted that ClassReflection::getNativeProperty() might be enough
        // https://github.com/phpstan/phpstan/discussions/4837

        try {
            $classReflection = $this->reflectionProvider->getClass($controllerClass);
            $propertyReflection = $classReflection->getProperty($propertyName, $scope);

            return $this->staticTypeMapper->mapPHPStanTypeToPHPStanPhpDocTypeNode($propertyReflection->getReadableType());
        } catch (MissingPropertyFromReflectionException $e) {
            return null;
        }
    }
}
