<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\DataCollector\DataExtractor\Extractor;

use ReflectionProperty;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * @template T of DataCollectorInterface
 *
 * @template-extends AbstractCollectorDataExtractorInterface<T>
 */
final readonly class GenericCollectorDataExtractor extends AbstractCollectorDataExtractorInterface
{
    public function extractData(): array
    {
        $data = null;

        if (method_exists($this->collector, 'getData')) {
            $data = $this->collector->getData();
        }

        if ($this->collector instanceof DataCollector) {
            $property = new ReflectionProperty(DataCollector::class, 'data');

            $data = $property->getValue($this->collector);
        }

        if (null === $data) {
            return ['warning' => 'Collector data could not be extracted'];
        }

        if ($data instanceof Data) {
            $data = $data->getValue(true);
        }

        return $data;
    }
}
