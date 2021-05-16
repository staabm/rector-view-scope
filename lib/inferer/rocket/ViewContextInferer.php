<?php

namespace ViewScopeRector\Inferer\Rocket;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Core\Provider\CurrentFileProvider;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\StaticTypeMapper\StaticTypeMapper;
use ViewScopeRector\ContextInferer;

/**
 * Implements view-variable type inferring for view-scripts in the Rocket-Framework (close-source) context.
 */
final class ViewContextInferer implements ContextInferer
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
     * @var CurrentFileProvider
     */
    private $currentFileProvider;
    /**
     * @var FileLocator
     */
    private $fileLocator;

    public function __construct(ReflectionProvider $reflectionProvider, NodeNameResolver $nodeNameResolver, StaticTypeMapper $staticTypeMapper, CurrentFileProvider $currentFileProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->nodeNameResolver = $nodeNameResolver;
        $this->staticTypeMapper = $staticTypeMapper;
        $this->currentFileProvider = $currentFileProvider;
    }

    public function infer(Variable $variable): ?TypeNode
    {
        $this->fileLocator = new \TestFileLocator($variable);

        if (!$this->fileLocator->isInViewPath()) {
            return null;
        }

        if (!$this->fileLocator->isTopLevelView()) {
            return null;
        }

        $controllerClass = $this->fileLocator->findMatchingController();
        if (!$controllerClass) {
            return null;
        }

        return $this->inferTypeFromController($controllerClass, $variable);
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