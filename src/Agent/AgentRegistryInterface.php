<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Agent;

use Symfony\AI\Agent\AgentInterface;

interface AgentRegistryInterface
{
    public function getAgent(string $agentName): AgentInterface;
}
