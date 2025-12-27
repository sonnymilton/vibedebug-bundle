<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Controller;

use League\CommonMark\ConverterInterface;
use Milton\VibedebugBundle\Agent\AgentRegistryInterface;
use Milton\VibedebugBundle\Agent\Chat\ChatLoader;
use Milton\VibedebugBundle\Agent\Exception\AgentNotFoundException;
use Milton\VibedebugBundle\DataCollector\VibedebugDataCollectorInterface;
use Symfony\AI\Platform\Message\Content\Text;
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
        private ChatLoader $chatLoader,
        private Environment $twig,
        private ConverterInterface $responseConverter,
    ) {
    }

    public function chat(string $agent, string $token, Request $request): JsonResponse
    {
        try {
            $agent = $this->agentRegistry->getAgent($agent);
        } catch (AgentNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }

        $chat = $this->chatLoader->loadChat($agent, $token);
        $message = new UserMessage(
            new Text($request->getPayload()->getString('prompt')),
        );

        try {
            $resultContent = (string) $chat->submit($message)->getContent();
        } catch (Throwable) {
            $resultContent = 'The agent didn\'t respond to the request';
        }

        return new JsonResponse([
            'result' => $this->responseConverter->convert($resultContent)->getContent(),
        ]);
    }

    public function prompt(string $token): Response
    {
        $profile = $this->profiler->loadProfile($token);

        if (null === $profile || !$profile->hasCollector('vibedebug')) {
            throw new NotFoundHttpException('Profile not found');
        }

        /** @var VibedebugDataCollectorInterface $collector */
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
