<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\DataCollector\DataExtractor\Extractor;

use Milton\VibedebugBundle\Agent\Tool\ProfileExporter;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpKernel\DataCollector\ExceptionDataCollector;

/**
 * @extends AbstractCollectorDataExtractorInterface<ExceptionDataCollector>
 *
 * @phpstan-import-type ExceptionExport from ProfileExporter
 */
final readonly class ExceptionCollectorDataExtractor extends AbstractCollectorDataExtractorInterface
{
    /**
     * @return array<ExceptionExport>
     */
    public function extractData(): array
    {
        if (!$this->collector->hasException()) {
            return [];
        }

        $exception = $this->collector->getException();

        if (!$exception instanceof FlattenException) {
            $exception = FlattenException::create($exception);
        }

        return $exception->toArray();
    }
}
