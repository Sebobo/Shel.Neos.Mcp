<?php

declare(strict_types=1);

namespace Shel\Neos\Mcp\Domain;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\Flow\Annotations as Flow;

/**
 * The preview of a document: the rendered page, not the content of the document.
 *
 * Every result carries two links. The preview of the own workspace shows the unpublished
 * changes of the agent and stays private: policies deny other users the personal workspace of
 * the agent. The preview of the workspace the agent hands its work over to is the link a human
 * opens - as a backend user, after the agent has published its work there.
 *
 * The request has to be authenticated: the agent sends its token, the same one it uses for the
 * MCP connection. Without it the frontend answers with the login page instead of the page. The
 * token only authenticates the agent; it is never part of a shared link.
 */
#[Flow\Proxy(false)]
class PreviewCapabilities
{
    public function __construct(
        protected MCPContentContextFactory $contentContextFactory,
        protected AgentWorkspaceResolver $agentWorkspaceResolver,
        protected PreviewUriBuilder $previewUriBuilder,
    ) {
    }

    /**
     * The preview URLs of a document - the page as it is rendered, not the content of the
     * document.
     *
     * ownWorkspacePreviewUrl shows the unpublished changes of the agent, and only the agent can
     * open it: it is the personal workspace, and policies deny other users the personal
     * workspace of another user.
     *
     * reviewWorkspacePreviewUrl is the link to send to a human: it shows the workspace the
     * agent hands its work over to, and the human opens it as a backend user. It contains only
     * what the agent has published there.
     *
     * @param string|array<string,string> $dimensionSpacePoint
     * @return array<string,mixed>
     */
    #[McpTool(
        name: 'PreviewDocument',
        description: 'The preview URLs of a document - the page as it is rendered, not the content of the document.
            ownWorkspacePreviewUrl shows the unpublished changes of the agent. Only the agent can open it: it is the
            personal workspace, and policies deny other users the personal workspace of another user. Never send it
            to a person.
            reviewWorkspacePreviewUrl is the link to send to a human: it shows the workspace the agent hands its work
            over to - the shared review workspace or the topic workspace. It shows only what the agent has already
            published there, so publish first; a document that only exists in the own workspace has no page in the
            review workspace. The human opens it in a browser where they are logged into the Neos backend.
            To fetch a preview URL yourself, send the same token you use for this connection, otherwise the frontend
            answers with the login page: curl -H "Authorization: Bearer <your token>" "<the url>". Never share the
            token with anyone.
            This tool does not fetch anything itself, it only tells you the URLs.',
        annotations: new ToolAnnotations(
            readOnlyHint: true,
            destructiveHint: false,
            idempotentHint: true,
            openWorldHint: false,
        )
    )]
    #[Schema(properties: McpSchemaLibrary::DIMENSION_SPACE_POINT_TOOL_SCHEMA['properties'])]
    public function previewDocument(
        #[Schema(
            type: 'string',
            description: 'The node aggregate id of the document to preview.',
        )]
        string $nodeAggregateId,
        string|array $dimensionSpacePoint = [],
    ): array {
        try {
            $contentContext = $this->contentContextFactory->getReadingContentContext(
                MCPContentContextFactory::decodeDimensionSpacePoint($dimensionSpacePoint)
            );

            $node = $contentContext->getNodeByIdentifier($nodeAggregateId);
            if (!$node instanceof Node) {
                throw new \RuntimeException(
                    \sprintf('No node found for nodeAggregateId %s.', $nodeAggregateId)
                );
            }

            $previewUrl = $this->previewUriBuilder->uriFor($node->getPath(), $node->getContextPath());
            if ($previewUrl === null) {
                throw new \RuntimeException(
                    'The site of the document has no domain it could be reached on, so it cannot be previewed.'
                );
            }

            $result = [
                'success' => true,
                'ownWorkspacePreviewUrl' => $previewUrl,
                'workspace' => $contentContext->getWorkspaceName(),
                'contextPath' => $node->getContextPath(),
                'nodeAggregateId' => $node->getIdentifier(),
                'nodeTypeName' => $node->getNodeTypeName(),
            ];

            $reviewWorkspacePreview = $this->reviewWorkspacePreview($node, $contentContext->getDimensions());
            if ($reviewWorkspacePreview !== null) {
                $result['reviewWorkspacePreviewUrl'] = $reviewWorkspacePreview['url'];
                $result['reviewWorkspace'] = $reviewWorkspacePreview['workspace'];
            }

            return $result;
        } catch (\Throwable $throwable) {
            return [
                'success' => false,
                'message' => $throwable->getMessage(),
            ];
        }
    }

    /**
     * The preview of the document in the workspace the agent hands its work over to - the
     * shared review workspace, or the topic workspace while the agent works on a topic.
     *
     * Null while that workspace is live or missing: a link to live is the page itself, and
     * nothing of the agent is reviewable there.
     *
     * @param array<string,list<string>> $dimensions
     * @return array{url: string, workspace: string}|null
     */
    private function reviewWorkspacePreview(Node $node, array $dimensions): ?array
    {
        $baseWorkspace = $this->agentWorkspaceResolver->getWorkspace()->getBaseWorkspace();
        if ($baseWorkspace === null || $baseWorkspace->getName() === SharedWorkspaceContextFactory::LIVE_WORKSPACE) {
            return null;
        }

        $contextPath = NodePaths::generateContextPath($node->getPath(), $baseWorkspace->getName(), $dimensions);
        $url = $this->previewUriBuilder->uriFor($node->getPath(), $contextPath);

        return $url === null ? null : ['url' => $url, 'workspace' => $baseWorkspace->getName()];
    }
}
