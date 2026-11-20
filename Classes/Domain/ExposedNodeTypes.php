<?php

declare(strict_types=1);

namespace Sitegeist\SlopMachine\Domain;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Annotations as Flow;

/**
 * The node types an MCP client may create: the non-abstract types marked with the MCPExposed
 * mixin, directly or through a super type.
 *
 * The old content repository never enforced abstractness on the write path - the editor UI
 * only hides abstract types from the creation dialog - so this set is what makes the schema
 * and the write tools agree: a node type that is listed is creatable, and nothing else is.
 * Abstract types stay what they are in Neos, a vocabulary for searching, never a template
 * for an instance.
 */
#[Flow\Scope('singleton')]
class ExposedNodeTypes
{
    public const MIXIN = 'Sitegeist.SlopMachine:Mixin.MCPExposed';

    public function __construct(
        protected NodeTypeManager $nodeTypeManager,
    ) {
    }

    /**
     * The creatable node types, keyed by name.
     *
     * @return array<string,NodeType>
     */
    public function all(): array
    {
        return $this->nodeTypeManager->getSubNodeTypes(self::MIXIN, false);
    }

    public function isCreatable(NodeType $nodeType): bool
    {
        return isset($this->all()[$nodeType->getName()]);
    }
}
