<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Tests\Agent\Chat;

use Milton\VibedebugBundle\Agent\Chat\ProfileStore;
use Milton\VibedebugBundle\DataCollector\VibedebugDataCollectorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\SystemMessage;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\ProfilerStorageInterface;

class ProfileStoreTest extends TestCase
{
    public function testConstructorThrowsWhenProfileIsNotSupported(): void
    {
        $storage = $this->createMock(ProfilerStorageInterface::class);

        $profile = $this->createMock(Profile::class);
        $profile->method('hasCollector')->with('vibedebug')->willReturn(false);
        $profile->method('getToken')->willReturn('tok-1');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Profile for token tok-1 is not supported');

        new ProfileStore($storage, $profile);
    }

    public function testSaveSetsMessageBagOnCollectorAndPersistsProfile(): void
    {
        $messages = new MessageBag();
        $messages->add(new SystemMessage('hi'));

        $collector = $this->createMock(VibedebugDataCollectorInterface::class);
        $collector
            ->expects(self::once())
            ->method('setChatMessageBag')
            ->with(self::identicalTo($messages));

        $profile = $this->supportedProfile($collector);

        $storage = $this->createMock(ProfilerStorageInterface::class);
        $storage
            ->expects(self::once())
            ->method('write')
            ->with(self::identicalTo($profile));

        $store = new ProfileStore($storage, $profile);
        $store->save($messages);
    }

    public function testLoadAddsSystemPromptWhenEmpty(): void
    {
        $bag = new MessageBag();

        $collector = $this->createMock(VibedebugDataCollectorInterface::class);
        $collector
            ->expects(self::once())
            ->method('getChatMessageBag')
            ->willReturn($bag);

        $profile = $this->supportedProfile($collector);

        $storage = $this->createMock(ProfilerStorageInterface::class);
        $storage->expects(self::never())->method('write');

        $prompt = new SystemMessage('system prompt');

        $store = new ProfileStore($storage, $profile, $prompt);
        $loaded = $store->load();

        self::assertSame($bag, $loaded);
        self::assertSame(2, $loaded->count());

        self::assertCount(2, $loaded);
        self::assertSame($prompt, $loaded->getSystemMessage());

        $messages = $loaded->getMessages();
        self::assertSame($prompt, $messages[0]);
        self::assertSame(
            'Context: Symfony Profiler profile token = tok-supported. Use it when you need to fetch profile data via tools.',
            $messages[1]->getContent()
        );
    }

    public function testLoadAddsContextMessageWhenEmptyAndNoSystemPrompt(): void
    {
        $bag = new MessageBag();

        $collector = $this->createMock(VibedebugDataCollectorInterface::class);
        $collector
            ->expects(self::once())
            ->method('getChatMessageBag')
            ->willReturn($bag);

        $profile = $this->supportedProfile($collector);

        $storage = $this->createMock(ProfilerStorageInterface::class);
        $storage->expects(self::never())->method('write');

        $store = new ProfileStore($storage, $profile);
        $loaded = $store->load();

        self::assertSame($bag, $loaded);
        self::assertSame(1, $loaded->count());

        $messages = $loaded->getMessages();
        self::assertSame(
            'Context: Symfony Profiler profile token = tok-supported. Use it when you need to fetch profile data via tools.',
            $messages[0]->getContent()
        );
    }

    public function testSetupResetsMessageBagAndPersistsProfile(): void
    {
        $collector = $this->createMock(VibedebugDataCollectorInterface::class);
        $collector
            ->expects(self::once())
            ->method('setChatMessageBag')
            ->with(self::callback(static function ($bag): bool {
                return $bag instanceof MessageBag && 0 === $bag->count();
            }));

        $profile = $this->supportedProfile($collector);

        $storage = $this->createMock(ProfilerStorageInterface::class);
        $storage
            ->expects(self::once())
            ->method('write')
            ->with(self::identicalTo($profile));

        $store = new ProfileStore($storage, $profile);
        $store->setup();
    }

    private function supportedProfile(VibedebugDataCollectorInterface $collector): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('hasCollector')->with('vibedebug')->willReturn(true);

        $profile
            ->method('getCollector')
            ->with('vibedebug')
            ->willReturn($collector);

        $profile->method('getToken')->willReturn('tok-supported');

        return $profile;
    }
}
