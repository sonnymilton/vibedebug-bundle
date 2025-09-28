<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Agent\Exception;

final class AgentNotFoundException extends \RuntimeException
{
    static function createFromAgentName(string $agentName)
    {
        return new self(sprintf('Agent "%s" not found', $agentName));
    }
}
