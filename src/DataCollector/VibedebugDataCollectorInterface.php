<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\DataCollector;

use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

interface VibedebugDataCollectorInterface extends DataCollectorInterface
{
    /**
     * @return array<string>
     */
    public function getAgents(): array;

    public function getException(): ?FlattenException;

    public function setChatMessageBag(MessageBag $messageBag): void;

    public function getChatMessageBag(): MessageBag;
}
