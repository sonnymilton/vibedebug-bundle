<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Twig\Extension;

use League\CommonMark\ConverterInterface;
use Stringable;
use Symfony\AI\Platform\Message\Content;
use Symfony\AI\Platform\Message\MessageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class VibedebugExtension extends AbstractExtension
{
    public function __construct(private readonly ConverterInterface $converter)
    {
    }

    public function getFilters()
    {
        return [
            new TwigFilter('vibedebug_content_extract', static function (MessageInterface $message): string {
                $content = $message->getContent();

                if (is_string($content) || $content instanceof Stringable) {
                    return (string) $content;
                }

                if (is_array($content)) {
                    foreach ($content as $item) {
                        if ($item instanceof Content\Text) {
                            return $item->getText();
                        }
                    }
                }

                return '**[VIBEDEBUG][ERROR] Message content is not supported**';
            }),
            new TwigFilter('vibedebug_markdown', $this->converter->convert(...), ['is_safe' => ['html']]),
        ];
    }
}
