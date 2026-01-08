<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\DataCollector\DataExtractor;

interface CollectorDataExtractorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function extractData(): array;
}
