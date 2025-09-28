<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Controller;

use Milton\VibedebugBundle\DataCollector\VibedebugDataCollector;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Throwable;
use Twig\Environment;

final class VibedebugController
{
    /**
     * @param iterable<AgentInterface> $agents
     */
    public function __construct(
        private readonly iterable $agents,
        private Profiler $profiler,
        private Environment $twig,
    )
    {
    }

    public function askAgent(string $agent, Request $request): JsonResponse
    {
        $agent = $this->getAgent($agent);
        $message = new UserMessage(
            new Text($request->getPayload()->getString('prompt')),
        );

        try {
            $resultContent = $agent->call(new MessageBag($message))->getContent();
        } catch (Throwable) {
            $resultContent = 'The agent didn\'t respond to the request';
        }

        return new JsonResponse([
            'result' => $resultContent,
        ]);
    }

    public function prompt(string $token): Response
    {
        $profile = $this->profiler->loadProfile($token);

        if (null === $profile || !$profile->hasCollector('vibedebug')) {
            throw new NotFoundHttpException('Profile not found');
        }

        /** @var VibedebugDataCollector $collector */
        $collector = $profile->getCollector('vibedebug');

        return new Response(
            content: $this->twig->render('@Vibedebug/data_collector/prompt.md.twig', [
                'exception' => $collector->getException(),
            ]),
            headers: [
                'Content-Type' => 'text/markdown',
            ]
        );
    }

    private function getAgent(string $agentName): AgentInterface
    {
        foreach ($this->agents as $agent) {
            if ($agent->getName() === $agentName) {
                return $agent;
            }
        }

        throw new NotFoundHttpException('Agent with name '.$agentName.' not found');
    }
}
