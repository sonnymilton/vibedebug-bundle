<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Tests\Agent;

use Milton\VibedebugBundle\Agent\AgentRegistry;
use Milton\VibedebugBundle\Agent\Exception\AgentNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\MockAgent;

class AgentRegistryTest extends TestCase
{
    public function testGetAgentOk(): void
    {
        $targetAgent = new MockAgent([], 'agent2');
        $agentRegistry = new AgentRegistry([
            new MockAgent([], 'agent1'),
            $targetAgent,
            new MockAgent([], 'agent3'),
        ]);

        $agent = $agentRegistry->getAgent('agent2');

        $this->assertSame($targetAgent, $agent);
    }

    public function testGetAgentFail(): void
    {
        $agentRegistry = new AgentRegistry([]);

        $this->expectException(AgentNotFoundException::class);

        $agentRegistry->getAgent('agent');
    }
}
