<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\DataCollector\Adapter;

use Milton\VibedebugBundle\DataCollector\ExtractableDataCollectorInterface;
use ReflectionProperty;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\VarDumper\Cloner\Data;

final readonly class GenericDataCollectorExtractorAdapter implements ExtractableDataCollectorInterface
{
    public function __construct(
        private DataCollectorInterface $collector,
    ) {
    }

    public static function for(DataCollectorInterface $collector): self
    {
        return new self($collector);
    }

    /**
     * @return Data|array<mixed>
     */
    public function extractData(): array|Data
    {
        if ($this->collector instanceof ExtractableDataCollectorInterface) {
            return $this->collector->extractData();
        }

        if (method_exists($this->collector, 'getData')) {
            return $this->collector->getData();
        }

        if ($this->collector instanceof DataCollector) {
            $property = new ReflectionProperty(DataCollector::class, 'data');

            $data = $property->getValue($this->collector);

            if ($data instanceof Data || is_array($data)) {
                return $data;
            }
        }

        return [
            'warning' => 'Collector data could not be extracted.',
        ];
    }
}
