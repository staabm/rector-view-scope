<?php

namespace ViewScopeRector;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
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
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
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
        return new RuleDefinition('Infer view scope', [new ConfiguredCodeSample('', '')]);
    }

    public function getNodeTypes(): array
    {
        return [Node\Stmt\Echo_::class];
    }

    /**
     * @param Node\Stmt\Echo_ $node
     */
    public function refactor(Node $node): ?Node
    {
        /*
array(
    0: Stmt_Echo(
        exprs: array(
            0: Expr_Variable(
                name: hello
            )
        )
    )
)
        */

        if (!isset($node->exprs[0]) || !$node->exprs[0] instanceof Variable) {
            return null;
        }

        $variable = $node->exprs[0];

        $inferredType = $this->inferTypeFromController("\IndexController", $variable);
        if (!$inferredType) {
            // no matching property for the given variable, skip.
            return null;
        }

        // https://github.com/rectorphp/rector/blob/main/docs/how_to_work_with_doc_block_and_comments.md
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $phpDocInfo->addTagValueNode(new VarTagValueNode($inferredType, '$' . $variable->name, ''));

        return $node;
    }

    /**
     * @param class-string $controllerClass
     */
    private function inferTypeFromController($controllerClass, Variable $node): ?TypeNode
    {
        /** @var Scope|null $scope */
        $scope = $node->getAttribute(AttributeKey::SCOPE);

        $propertyName = $node->name;

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

    /*
    private function createParamDocNode(): PhpDocNode
    {
        $paramTagValueNode = new ParamTagValueNode(new IdentifierTypeNode('string'), true, 'name', '');

        $children = [new PhpDocTagNode('@param', $paramTagValueNode)];

        return new PhpDocNode($children);
    }
    */
}
