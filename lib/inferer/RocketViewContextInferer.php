<?php

namespace ViewScopeRector\Inferer;

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
 *
 * @implements ContextInferer<Variable>
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
     * @var CurrentFileProvider
     */
    private $currentFileProvider;

    public function __construct(ReflectionProvider $reflectionProvider, NodeNameResolver $nodeNameResolver, StaticTypeMapper $staticTypeMapper, CurrentFileProvider $currentFileProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->nodeNameResolver = $nodeNameResolver;
        $this->staticTypeMapper = $staticTypeMapper;
        $this->currentFileProvider = $currentFileProvider;
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

    private function isInViewPath(Variable $variable): bool
    {
        // TODO implement me
        return true;
    }

    private function isTopLevelView(Variable $variable): bool
    {
        // TODO implement me
        return true;
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

    private function inferTypeFromController(string $controllerClass, Variable $node): TypeNode
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
        $propertyReflection = $classReflection->getProperty($propertyName, $scope);

        return $this->staticTypeMapper->mapPHPStanTypeToPHPStanPhpDocTypeNode($propertyReflection->getReadableType());
    }
}
