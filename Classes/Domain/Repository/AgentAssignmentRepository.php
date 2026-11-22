<?php

declare(strict_types=1);

namespace Shel\Neos\Mcp\Domain\Repository;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\Repository;
use Neos\Flow\Security\Context;
use Shel\Neos\Mcp\Domain\Model\AgentAssignment;

#[Flow\Scope('singleton')]
class AgentAssignmentRepository extends Repository
{
    #[Flow\Inject]
    protected Context $securityContext;

    public function findOneByCurrentAccount(): ?AgentAssignment
    {
        $account = $this->securityContext->getAccount();

        return $account ? $this->findOneByAgentId($account->getAccountIdentifier()) : null;
    }
}
