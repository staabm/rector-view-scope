<?php

namespace ViewScopeRector\Inferer;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\StaticTypeMapper\StaticTypeMapper;
use ViewScopeRector\ContextInferer;

/**
 * @implements ContextInferer<Variable>
 */
final class RocketViewContextInferer implements ContextInferer {
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

    public function __construct(ReflectionProvider $reflectionProvider, NodeNameResolver $nodeNameResolver, StaticTypeMapper $staticTypeMapper) {
        $this->reflectionProvider = $reflectionProvider;
        $this->nodeNameResolver = $nodeNameResolver;
        $this->staticTypeMapper = $staticTypeMapper;
    }

    public function infer(Node $variable): ?TypeNode
    {
        if (!$variable instanceof Variable) {
            throw new \RuntimeException("should not happen");
        }

        if (!$this->isInViewPath($variable)) {
            return null;
        }

        if (!$this->isTopLevelView($variable)) {
            return null;
        }

        $controllerClass = $this->findMatchingController($variable);
        if (!$controllerClass) {
            return null;
        }

        return $this->inferTypeFromController($controllerClass, $variable);
    }

    private function isInViewPath(Variable $variable):bool {
        // TODO implement me
        return true;
    }

    private function isTopLevelView(Variable $variable):bool {
        // TODO implement me
        return true;
    }

    /**
     * @return class-string|null
     */
    private function findMatchingController(Variable $variable): ?string
    {
        // TODO implement me
        return "\IndexController";
    }

    /**
     * @param class-string $controllerClass
     */
    private function inferTypeFromController($controllerClass, Variable $node): ?TypeNode
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
        $classReflection = $this->reflectionProvider->getClass($controllerClass);

        try {
            $propertyReflection = $classReflection->getProperty($propertyName, $scope);
        } catch (MissingPropertyFromReflectionException $e) {
            return null;
        }

        return $this->staticTypeMapper->mapPHPStanTypeToPHPStanPhpDocTypeNode($propertyReflection->getReadableType());
    }
}
