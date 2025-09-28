<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Controller;

use Milton\VibedebugBundle\Agent\AgentRegistryInterface;
use Milton\VibedebugBundle\Agent\Exception\AgentNotFoundException;
use Milton\VibedebugBundle\DataCollector\VibedebugDataCollector;
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

final readonly class VibedebugController
{
    public function __construct(
        private AgentRegistryInterface $agentRegistry,
        private Profiler $profiler,
        private Environment $twig,
    ) {
    }

    public function askAgent(string $agent, Request $request): JsonResponse
    {
        try {
            $agent = $this->agentRegistry->getAgent($agent);
        } catch (AgentNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }

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
}
