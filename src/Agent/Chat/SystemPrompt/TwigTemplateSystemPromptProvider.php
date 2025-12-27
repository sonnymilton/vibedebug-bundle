<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Agent\Chat\SystemPrompt;

use Symfony\AI\Platform\Message\SystemMessage;
use Twig\Environment;

final readonly class TwigTemplateSystemPromptProvider implements SystemPromptProviderInterface
{
    public function __construct(
        private string $template,
        private Environment $twig,
    ) {
    }

    public function getSystemMessage(): ?SystemMessage
    {
        $content = $this->twig->render($this->template);

        if ('' === $content) {
            return null;
        }

        return new SystemMessage($content);
    }
}
