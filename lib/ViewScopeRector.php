<?php

namespace ViewScopeRector;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeDumper;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class ViewScopeRector extends AbstractRector
{
    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider
    )
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Infer view scope', [new CodeSample('', '')]);
    }

    public function getNodeTypes(): array
    {
        return [Variable::class];
    }

    /**
     * @param Variable $variable
     */
    public function refactor(Node $variable): ?Node
    {
        if (!$this->isInViewPath($variable)) {
            return null;
        }
        
        if (!$this->isTopLevelView($variable)) {
            return null;
        }

        $controllerClass = $this->findMatchingController($variable));
        if (!$controllerClass) {
            return null;
        }

        $inferredType = $this->inferTypeFromController($controllerClass, $variable);
        if (!$inferredType) {
            // no matching property for the given variable, skip.
            return null;
        }

        $this->declareClassLevelDocBlock($variable, $inferredType);

        return $variable;
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
    private function findMatchingController(Variable $variable): ?string {
        // TODO implement me
        return "\IndexController";
    }

    /**
     * @return void
     */
    private function declareClassLevelDocBlock(Variable $variable, TypeNode $inferredType)
    {
        $statement = $this->findFirstViewStatement($variable);
        
        $variableName = $this->nodeNameResolver->getName($variable);
        if ($variableName === null) {
            throw new \RuntimeException("should not happen");
        }

        // https://github.com/rectorphp/rector/blob/main/docs/how_to_work_with_doc_block_and_comments.md
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($statement);

        $found = null;
        foreach ($phpDocInfo->getPhpDocNode()->getVarTagValues() as $varTagValue) {
            if ($varTagValue->variableName == '$' . $variableName) {
                $found = $varTagValue;
                break;
            }
        }

        if (!$found) {
            $phpDocInfo->addTagValueNode(new VarTagValueNode($inferredType, '$' . $variableName, ''));
        } else {
            $found->type = $inferredType;
        }
    }

    private function findFirstViewStatement(Variable $node): Node
    {
        $topLevelParent = $this->findTopLevelStatement($node);

        $current = $topLevelParent;

        do {
            $previous = $current->getAttribute(AttributeKey::PREVIOUS_NODE);

            if (!$previous instanceof Node) {
                return $current;
            }

            $current = $previous;

        } while (true);
    }

    private function findTopLevelStatement(Variable $node): Node
    {
        $parent = $node->getAttribute(AttributeKey::PARENT_NODE);
        if (!$parent instanceof Node) {
            throw new \RuntimeException("should not happen");
        }

        $toplevelParent = $parent;

        do {
            $parent = $parent->getAttribute(AttributeKey::PARENT_NODE);

            if (!$parent instanceof Node) {
                return $toplevelParent;
            }

            $toplevelParent = $parent;

        } while (true);
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
