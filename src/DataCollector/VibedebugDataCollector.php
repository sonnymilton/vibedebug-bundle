<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\DataCollector;

use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;

final class VibedebugDataCollector extends DataCollector
{
    /**
     * @param iterable<AgentInterface> $agents
     */
    public function __construct(
        private readonly iterable $agents,
    ) {
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null)
    {
        $this->data['agents'] = array_map(static fn (AgentInterface $agent): string => $agent->getName(), iterator_to_array($this->agents));

        if (null !== $exception) {
            $this->data['exception'] = FlattenException::createWithDataRepresentation($exception);
        }
    }

    public function getName(): string
    {
        return 'vibedebug';
    }

    /**
     * @return array<string>
     */
    public function getAgents(): array
    {
        return $this->data['agents'];
    }

    public function getException(): ?FlattenException
    {
        return $this->data['exception'];
    }
}
