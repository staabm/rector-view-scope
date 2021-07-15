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
use Rector\PHPStanStaticTypeMapper\ValueObject\TypeKind;
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
     * @var FileLocator
     */
    private $fileLocator;

    public function __construct(ReflectionProvider $reflectionProvider, NodeNameResolver $nodeNameResolver, StaticTypeMapper $staticTypeMapper, FileLocator $fileLocator)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->nodeNameResolver = $nodeNameResolver;
        $this->staticTypeMapper = $staticTypeMapper;
        $this->fileLocator = $fileLocator;
    }

    public function infer(Variable $variable): ?TypeNode
    {
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
        $propertyName = $this->nodeNameResolver->getName($node);
        if ($propertyName === null) {
            return null;
            // XXX
            // throw new \RuntimeException("should not happen");
        }

        try {
            $classReflection = $this->reflectionProvider->getClass($controllerClass);
            $propertyReflection = $classReflection->getNativeProperty($propertyName);

            return $this->staticTypeMapper->mapPHPStanTypeToPHPStanPhpDocTypeNode($propertyReflection->getReadableType(), TypeKind::PROPERTY());
        } catch (MissingPropertyFromReflectionException $e) {
            return null;
        }
    }
}
