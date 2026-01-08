<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Tests\DataCollector\DataExtractor\Extractor;

use Milton\VibedebugBundle\DataCollector\DataExtractor\Extractor\GenericCollectorDataExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Throwable;

final class GenericCollectorDataExtractorTest extends TestCase
{
    public function testExtractDataUsesGetDataWhenCollectorHasGetDataMethod(): void
    {
        $collector = new class implements DataCollectorInterface {
            /** @return array<string, mixed> */
            public function getData(): array
            {
                return ['foo' => 'bar'];
            }

            public function collect(Request $request, Response $response, ?Throwable $exception = null): void
            {
            }

            public function reset(): void
            {
            }

            public function getName(): string
            {
                return 'with_get_data';
            }
        };

        $extractor = new GenericCollectorDataExtractor($collector);

        self::assertSame(['foo' => 'bar'], $extractor->extractData());
    }

    public function testExtractDataReturnsWarningWhenDataCannotBeExtracted(): void
    {
        $collector = new class implements DataCollectorInterface {
            public function collect(Request $request, Response $response, ?Throwable $exception = null): void
            {
            }

            public function reset(): void
            {
            }

            public function getName(): string
            {
                return 'no_data';
            }
        };
        $extractor = new GenericCollectorDataExtractor($collector);

        self::assertSame(
            ['warning' => 'Collector data could not be extracted'],
            $extractor->extractData(),
        );
    }

    public function testExtractDataReturnsWarningWhenGetDataReturnsNull(): void
    {
        $collector = new class implements DataCollectorInterface {
            public function getData(): mixed
            {
                return null;
            }

            public function collect(Request $request, Response $response, ?Throwable $exception = null): void
            {
            }

            public function reset(): void
            {
            }

            public function getName(): string
            {
                return 'null_get_data';
            }
        };

        $extractor = new GenericCollectorDataExtractor($collector);

        self::assertSame(
            ['warning' => 'Collector data could not be extracted'],
            $extractor->extractData(),
        );
    }

    public function testExtractDataUnwrapsVarDumperData(): void
    {
        $varData = (new VarCloner())->cloneVar(['x' => 123]);
        $collector = new class($varData) extends DataCollector {
            public function __construct(array|Data $dataValue)
            {
                $this->data = $dataValue;
            }

            public function collect(Request $request, Response $response, ?Throwable $exception = null): void
            {
            }

            public function getName(): string
            {
                return 'data_collector';
            }
        };
        $extractor = new GenericCollectorDataExtractor($collector);

        self::assertSame(['x' => 123], $extractor->extractData());
    }
}
