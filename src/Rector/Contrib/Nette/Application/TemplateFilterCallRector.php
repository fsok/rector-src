<?php declare(strict_types=1);

namespace Rector\Rector\Contrib\Nette\Application;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use Rector\Node\NodeFactory;
use Rector\NodeAnalyzer\MethodCallAnalyzer;
use Rector\Rector\AbstractRector;

/**
 * Before:
 * $this->template->someFilter(...);
 *
 * After:
 * $this->template->getLatte()->invokeFilter('someFilter', ...)
 */
final class TemplateFilterCallRector extends AbstractRector
{
    /**
     * @var MethodCallAnalyzer
     */
    private $methodCallAnalyzer;
    /**
     * @var NodeFactory
     */
    private $nodeFactory;

    public function __construct(MethodCallAnalyzer $methodCallAnalyzer, NodeFactory $nodeFactory)
    {
        $this->methodCallAnalyzer = $methodCallAnalyzer;
        $this->nodeFactory = $nodeFactory;
    }

    public function isCandidate(Node $node): bool
    {
        return $this->methodCallAnalyzer->isMagicMethodCallOnType($node, 'Nette\Bridges\ApplicationLatte\Template');
    }

    /**
     * @param MethodCall $methodCallNode
     * @return Node
     */
    public function refactor(Node $methodCallNode): Node
    {
        $filterName = $methodCallNode->name->toString();
        $filterArguments = $methodCallNode->args;

        $methodCallNode->name = new Identifier('invokeFilter');
        $methodCallNode->args[0] = $this->nodeFactory->createArg($filterName);

        $methodCallNode->args = array_merge($methodCallNode->args, $filterArguments);

        $propertyFetchNode = new PropertyFetch($methodCallNode->var->var, $methodCallNode->var->name);

        $methodCallNode->var = $this->nodeFactory->createMethodCallWithVariable($propertyFetchNode, 'getLatte');

        return $methodCallNode;
    }
}
