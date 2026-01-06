<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Tests\DataCollector\Adapter;

use Milton\VibedebugBundle\DataCollector\Adapter\GenericDataCollectorExtractorAdapter;
use Milton\VibedebugBundle\DataCollector\ExtractableDataCollectorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\VarDumper\Cloner\Data;

class GenericDataCollectorExtractorAdapterTest extends TestCase
{
    public function testExtractPrefersExtractableDataCollectorInterface(): void
    {
        $collector = new class() implements DataCollectorInterface, ExtractableDataCollectorInterface {
            public function extractData(): array|Data
            {
                return ['source' => 'extractable'];
            }

            public function getData(): array
            {
                return ['source' => 'getData'];
            }

            public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
            {
            }

            public function getName(): string
            {
                return 'extractable';
            }

            public function reset(): void
            {
            }
        };

        $adapter = GenericDataCollectorExtractorAdapter::for($collector);

        self::assertSame(['source' => 'extractable'], $adapter->extractData());
    }

    public function testExtractUsesGetDataWhenAvailable(): void
    {
        $collector = new class() implements DataCollectorInterface {
            public function getData(): array
            {
                return ['source' => 'getData'];
            }

            public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
            {
            }

            public function getName(): string
            {
                return 'get-data';
            }

            public function reset(): void
            {
            }
        };

        $adapter = new GenericDataCollectorExtractorAdapter($collector);

        self::assertSame(['source' => 'getData'], $adapter->extractData());
    }

    public function testExtractFallsBackToDataCollectorData(): void
    {
        $collector = new class() extends DataCollector {
            public function __construct()
            {
                $this->data = ['source' => 'data'];
            }

            public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
            {
            }

            public function getName(): string
            {
                return 'data-collector';
            }
        };

        $adapter = new GenericDataCollectorExtractorAdapter($collector);

        self::assertSame(['source' => 'data'], $adapter->extractData());
    }

    public function testExtractReturnsWarningWhenUnsupported(): void
    {
        $collector = new class() implements DataCollectorInterface {
            public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
            {
            }

            public function getName(): string
            {
                return 'unsupported';
            }

            public function reset(): void
            {
            }
        };

        $adapter = new GenericDataCollectorExtractorAdapter($collector);

        self::assertSame(['warning' => 'Collector data could not be extracted.'], $adapter->extractData());
    }
}
