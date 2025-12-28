<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\DataCollector;

use Symfony\Component\VarDumper\Cloner\Data;

interface ExtractableDataCollectorInterface
{
    /**
     * @return array<mixed>|Data
     */
    public function extractData(): array|Data;
}
