<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Agent\Chat;

use Milton\VibedebugBundle\Agent\Chat\Exception\ProfileNotFoundException;
use Milton\VibedebugBundle\Agent\Chat\SystemPrompt\SystemPromptProviderInterface;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Chat\Chat;
use Symfony\Component\HttpKernel\Profiler\ProfilerStorageInterface;

final readonly class ChatLoader
{
    public function __construct(
        private ProfilerStorageInterface $profilerStorage,
        private SystemPromptProviderInterface $systemPromptProvider,
    ) {
    }

    public function loadChat(AgentInterface $agent, string $profileToken): Chat
    {
        $profile = $this->profilerStorage->read($profileToken);

        if (null === $profile) {
            throw new ProfileNotFoundException(sprintf('Profile "%s" not found.', $profileToken));
        }

        $store = new ProfileStore($this->profilerStorage, $profile, $this->systemPromptProvider->getSystemMessage());

        return new Chat($agent, $store);
    }
}
