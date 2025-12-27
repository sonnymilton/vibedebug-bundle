<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Tests\Agent\Chat;

use Milton\VibedebugBundle\Agent\Chat\ChatLoader;
use Milton\VibedebugBundle\Agent\Chat\Exception\ProfileNotFoundException;
use Milton\VibedebugBundle\Agent\Chat\SystemPrompt\SystemPromptProviderInterface;
use Milton\VibedebugBundle\DataCollector\VibedebugDataCollector;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Chat\Chat;
use Symfony\AI\Platform\Message\SystemMessage;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\ProfilerStorageInterface;

class ChatLoaderTest extends TestCase
{
    public function testItThrowsWhenProfileNotFound(): void
    {
        $token = 'missing-token';

        $storage = $this->createMock(ProfilerStorageInterface::class);
        $storage
            ->expects(self::once())
            ->method('read')
            ->with($token)
            ->willReturn(null);

        $systemPromptProvider = $this->createMock(SystemPromptProviderInterface::class);
        $systemPromptProvider
            ->expects(self::never())
            ->method('getSystemMessage');

        $loader = new ChatLoader($storage, $systemPromptProvider);

        $this->expectException(ProfileNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Profile "%s" not found.', $token));

        $loader->loadChat($this->createMock(AgentInterface::class), $token);
    }

    public function testItReturnsChatWithProfileStoreAndSystemPrompt(): void
    {
        $token = 'token-123';

        $profile = new Profile($token);
        $profile->addCollector(
            $this->createConfiguredMock(DataCollectorInterface::class, ['getName' => 'vibedebug'])
        );

        $systemMessage = new SystemMessage('System prompt');

        $storage = $this->createMock(ProfilerStorageInterface::class);
        $storage
            ->expects(self::once())
            ->method('read')
            ->with($token)
            ->willReturn($profile);

        $systemPromptProvider = $this->createMock(SystemPromptProviderInterface::class);
        $systemPromptProvider
            ->expects(self::once())
            ->method('getSystemMessage')
            ->willReturn($systemMessage);

        $agent = $this->createMock(AgentInterface::class);

        $loader = new ChatLoader($storage, $systemPromptProvider);
        $chat = $loader->loadChat($agent, $token);

        self::assertInstanceOf(Chat::class, $chat);
    }
}
