<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Agent\Exception;

use RuntimeException;

final class AgentNotFoundException extends RuntimeException
{
    public static function createFromAgentName(string $agentName): self
    {
        return new self(sprintf('Agent "%s" not found', $agentName));
    }
}
