<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Agent\Chat;

use Milton\VibedebugBundle\DataCollector\VibedebugDataCollectorInterface;
use RuntimeException;
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
            throw new RuntimeException(sprintf('Profile for token %s is not supported', $this->profile->getToken()));
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

        if (0 === $messageBag->count() && null !== $this->systemPrompt) {
            $messageBag->add($this->systemPrompt);
        }

        return $messageBag;
    }

    public function setup(array $options = []): void
    {
        $this->collector()->setChatMessageBag(new MessageBag());
        $this->persist();
    }

    public function drop(): void
    {
        $this->setup();
    }

    private function collector(): VibedebugDataCollectorInterface
    {
        /* @var VibedebugDataCollectorInterface */
        return $this->profile->getCollector('vibedebug');
    }

    private function persist(): void
    {
        $this->storage->write($this->profile);
    }
}
