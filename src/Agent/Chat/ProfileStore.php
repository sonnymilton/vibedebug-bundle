<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Agent\Chat;

use Milton\VibedebugBundle\Agent\Chat\Exception\UnsupportedProfileException;
use Milton\VibedebugBundle\DataCollector\VibedebugDataCollectorInterface;
use Symfony\AI\Chat\ManagedStoreInterface;
use Symfony\AI\Chat\MessageStoreInterface;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\SystemMessage;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\ProfilerStorageInterface;

final readonly class ProfileStore implements MessageStoreInterface, ManagedStoreInterface
{
    public function __construct(
        private ProfilerStorageInterface $storage,
        private Profile $profile,
        private ?SystemMessage $systemPrompt = null,
    ) {
        if (!$this->profile->hasCollector('vibedebug')) {
            throw UnsupportedProfileException::forProfile($this->profile);
        }
    }

    public function save(MessageBag $messages): void
    {
        $this->collector()->setChatMessageBag($messages);
        $this->persist();
    }

    public function load(): MessageBag
    {
        $messageBag = $this->collector()->getChatMessageBag();

        if (0 === $messageBag->count()) {
            if (null !== $this->systemPrompt) {
                $messageBag->add($this->systemPrompt);
            }

            $messageBag->add(new SystemMessage(sprintf(
                'Context: Symfony Profiler profile token = %s. Use it when you need to fetch profile data via tools.',
                $this->profile->getToken(),
            )));
        }

        return $messageBag;
    }

    public function setup(array $options = []): void
    {
        $this->collector()->setChatMessageBag($this->load());
        $this->persist();
    }

    public function drop(): void
    {
        $this->setup();
    }

    private function collector(): VibedebugDataCollectorInterface
    {
        $collector = $this->profile->getCollector('vibedebug');

        if (!$collector instanceof VibedebugDataCollectorInterface) {
            throw UnsupportedProfileException::forProfile($this->profile);
        }

        return $collector;
    }

    private function persist(): void
    {
        $this->storage->write($this->profile);
    }
}
