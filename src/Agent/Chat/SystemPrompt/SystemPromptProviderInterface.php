<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Agent\Chat\SystemPrompt;

use Symfony\AI\Platform\Message\SystemMessage;

interface SystemPromptProviderInterface
{
    public function getSystemMessage(): ?SystemMessage;
}
