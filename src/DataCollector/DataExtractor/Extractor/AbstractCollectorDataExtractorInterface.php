<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\DataCollector\DataExtractor\Extractor;

use Milton\VibedebugBundle\DataCollector\DataExtractor\CollectorDataExtractorInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * @template T of DataCollectorInterface
 */
abstract readonly class AbstractCollectorDataExtractorInterface implements CollectorDataExtractorInterface
{
    /**
     * @param T $collector
     */
    final public function __construct(
        protected DataCollectorInterface $collector,
    ) {
    }

    /**
     * @template TNew of DataCollectorInterface
     *
     * @param TNew $dataCollector
     *
     * @return static<TNew>
     */
    public static function for(DataCollectorInterface $dataCollector): static
    {
        return new static($dataCollector);
    }

    abstract public function extractData(): array;
}
