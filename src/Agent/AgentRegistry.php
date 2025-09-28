<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Agent;

use Milton\VibedebugBundle\Agent\Exception\AgentNotFoundException;
use Symfony\AI\Agent\AgentInterface;

final readonly class AgentRegistry implements AgentRegistryInterface
{
    /**
     * @param iterable<AgentInterface> $agents
     */
    public function __construct(
        private iterable $agents,
    ) {
    }

    public function getAgent(string $agentName): AgentInterface
    {
        foreach ($this->agents as $agent) {
            if ($agent->getName() === $agentName) {
                return $agent;
            }
        }

        throw AgentNotFoundException::createFromAgentName($agentName);
    }
}
