<?php

namespace ViewScopeRector;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Core\Rector\AbstractRector;
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

        $inferredType = $this->inferTypeFromController("\IndexController", $node->exprs[0]->name);
        if (!$inferredType) {
            return null;
        }

        // https://github.com/rectorphp/rector/blob/main/docs/how_to_work_with_doc_block_and_comments.md
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $phpDocInfo->addTagValueNode(new VarTagValueNode($inferredType, '$' . $node->exprs[0]->name, ''));

        return $node;
    }

    /**
     * @param class-string $controllerClass
     * @param string $propertyName
     * @throws \PHPStan\Reflection\MissingPropertyFromReflectionException
     */
    private function inferTypeFromController($controllerClass, $propertyName): TypeNode
    {
        $classReflection = $this->reflectionProvider->getClass($controllerClass);
        // XXX where to get the $scope from?
        $propertyReflection = $classReflection->getProperty($propertyName);

        return $propertyReflection->getReadableType();
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
