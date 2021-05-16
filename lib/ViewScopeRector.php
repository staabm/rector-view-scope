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
use Rector\Core\Provider\CurrentFileProvider;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\RuleDocGenerator\ValueObject\AbstractCodeSample;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use ViewScopeRector\Inferer\Rocket\ViewContextInferer;

class ViewScopeRector extends AbstractRector
{
    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    /**
     * @var CurrentFileProvider
     */
    private $currentFileProvider;

    public function __construct(ReflectionProvider $reflectionProvider, CurrentFileProvider $currentFileProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->currentFileProvider = $currentFileProvider;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Defines @var types for local variables in a view-script, infered from a external context.', [new CodeSample('', '')]);
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
        $contextInferer = new ViewContextInferer($this->reflectionProvider, $this->nodeNameResolver, $this->staticTypeMapper, $this->currentFileProvider);

        $inferredType = $contextInferer->infer($variable);
        if (!$inferredType) {
            // no matching property for the given variable, skip.
            return null;
        }

        $this->declareClassLevelDocBlock($variable, $inferredType);

        return $variable;
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


}
