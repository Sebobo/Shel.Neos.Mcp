<?php

declare(strict_types=1);

namespace Shel\Neos\Mcp\Tests\Functional;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Sitegeist\Pandora\Infrastructure\CapabilityHandlerContainer;
use Shel\Neos\Mcp\Domain\PreviewCapabilities;

/**
 * The preview of a document: the URLs the agent fetches to see the rendered page.
 *
 * Every result names two workspaces: the own workspace of the agent, whose preview stays
 * private, and the workspace it hands its work over to, whose preview a human can open. Neither
 * URL carries a credential: the agent sends its token with the request, not in the URL.
 */
class PreviewCapabilitiesTest extends AgentTestCase
{
    public function testTheOwnWorkspacePreviewPointsAtTheWorkspaceOfTheAgent(): void
    {
        $result = $this->preview();

        self::assertTrue($result['success'] ?? false, 'The agent gets the preview of its own document.');
        self::assertStringContainsString(
            '@' . $this->agentWorkspace->getName(),
            (string)$result['contextPath'],
            'The preview shows the workspace of the agent.'
        );
        self::assertStringContainsString(
            urlencode('@' . $this->agentWorkspace->getName()),
            (string)$result['ownWorkspacePreviewUrl'],
            'The URL of the own workspace preview names the workspace of the agent.'
        );
        self::assertStringContainsString('/neos/preview', (string)$result['ownWorkspacePreviewUrl']);
    }

    public function testTheReviewWorkspacePreviewPointsAtTheWorkspaceTheAgentHandsItsWorkTo(): void
    {
        $result = $this->preview();

        self::assertArrayHasKey('reviewWorkspacePreviewUrl', $result);
        self::assertSame($this->reviewWorkspace->getName(), $result['reviewWorkspace'] ?? null);
        self::assertStringContainsString(
            urlencode('@' . $this->reviewWorkspace->getName()),
            (string)$result['reviewWorkspacePreviewUrl'],
            'The review workspace preview shows the workspace the agent hands its work over to.'
        );
        self::assertStringNotContainsString(
            urlencode('@' . $this->agentWorkspace->getName()),
            (string)$result['reviewWorkspacePreviewUrl'],
            'The review workspace preview is not the private preview of the agent.'
        );
    }

    public function testThePreviewUrlsCarryNoCredential(): void
    {
        $result = $this->preview();

        self::assertStringNotContainsString(self::AGENT_ID, (string)$result['ownWorkspacePreviewUrl']);
        self::assertStringNotContainsString(self::AGENT_ID, (string)$result['reviewWorkspacePreviewUrl']);
        self::assertStringNotContainsString('Bearer', (string)$result['ownWorkspacePreviewUrl']);
        self::assertStringNotContainsString('Bearer', (string)$result['reviewWorkspacePreviewUrl']);
    }

    public function testThePreviewOfADocumentOfAnotherWorkspaceIsNotOffered(): void
    {
        // The document is in the personal workspace of the other user, so the agent cannot
        // resolve it: the tool looks the node up in the workspace of the agent.
        self::assertNotNull($this->otherUserDocumentId);

        $result = $this->preview($this->otherUserDocumentId);

        self::assertFalse($result['success'] ?? true, 'The agent does not get a preview of a document it cannot see.');
        self::assertArrayNotHasKey('ownWorkspacePreviewUrl', $result);
    }

    /**
     * The preview of a document of the agent.
     *
     * @return array<string,mixed>
     */
    protected function preview(?string $nodeAggregateId = null): array
    {
        $document = $this->documentIn($this->agentWorkspace);
        self::assertInstanceOf(NodeInterface::class, $document);

        $capabilities = $this->getObject(CapabilityHandlerContainer::class)
            ->get(PreviewCapabilities::class);
        self::assertInstanceOf(PreviewCapabilities::class, $capabilities);

        return $capabilities->previewDocument(
            $nodeAggregateId ?? $document->getIdentifier(),
            $this->dimensionSpacePoint()
        );
    }
}
