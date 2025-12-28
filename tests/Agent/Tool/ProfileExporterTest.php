<?php

declare(strict_types=1);

namespace Agent\Tool;

use Milton\VibedebugBundle\Agent\Tool\ProfileExporter;
use Milton\VibedebugBundle\DataCollector\ExtractableDataCollectorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\ExceptionDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\VarDumper\Cloner\Data;

class ProfileExporterTest extends TestCase
{
    public function testExportSummaryReturnsErrorWhenProfileMissing(): void
    {
        $profiler = $this->createMock(Profiler::class);
        $profiler
            ->expects(self::once())
            ->method('loadProfile')
            ->with('tok-missing')
            ->willReturn(null);

        $exporter = new ProfileExporter($profiler);

        self::assertSame(
            ['error' => 'Profile "tok-missing" not found.', 'token' => 'tok-missing'],
            $exporter->exportSummary('tok-missing')
        );
    }

    public function testExportSummaryReturnsProfileDataAndCollectors(): void
    {
        $profile = new Profile('tok-1');
        $profile->setIp('127.0.0.1');
        $profile->setMethod('GET');
        $profile->setUrl('https://example.test/test');
        $profile->setTime(1700000000);
        $profile->setStatusCode(500);
        $profile->setParent(new Profile('parent-1'));
        $profile->addChild(new Profile('child-1'));

        $exceptionCollector = new ExceptionDataCollector();
        $exceptionCollector->collect(new Request(), new Response(), new \RuntimeException('Boom'));
        $profile->addCollector($exceptionCollector);

        $profile->addCollector(new class() implements DataCollectorInterface {
            public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
            {
            }

            public function getName(): string
            {
                return 'custom';
            }

            public function reset(): void
            {
            }
        });

        $profiler = $this->createMock(Profiler::class);
        $profiler
            ->expects(self::once())
            ->method('loadProfile')
            ->with('tok-1')
            ->willReturn($profile);

        $exporter = new ProfileExporter($profiler);
        $result = $exporter->exportSummary('tok-1');

        self::assertSame('tok-1', $result['token']);
        self::assertSame('parent-1', $result['parent']);
        self::assertSame(['child-1'], $result['children']);
        self::assertSame('127.0.0.1', $result['ip']);
        self::assertSame('GET', $result['method']);
        self::assertSame('https://example.test/test', $result['url']);
        self::assertSame(1700000000, $result['time']);
        self::assertSame(500, $result['status_code']);
        self::assertSame(['exception', 'custom'], $result['collectors']);

        self::assertInstanceOf(FlattenException::class, $result['exception']);
        self::assertSame('Boom', $result['exception']->getMessage());
    }

    public function testExportCollectorsToolReturnsErrorWhenProfileMissing(): void
    {
        $profiler = $this->createMock(Profiler::class);
        $profiler
            ->expects(self::once())
            ->method('loadProfile')
            ->with('tok-missing')
            ->willReturn(null);

        $exporter = new ProfileExporter($profiler);

        self::assertSame(
            ['error' => 'Profile "tok-missing" not found.', 'token' => 'tok-missing'],
            $exporter->exportCollectorsTool('tok-missing', ['custom'])
        );
    }

    public function testExportCollectorsToolReturnsErrorWhenCollectorMissing(): void
    {
        $profile = new Profile('tok-2');

        $profiler = $this->createMock(Profiler::class);
        $profiler
            ->expects(self::once())
            ->method('loadProfile')
            ->with('tok-2')
            ->willReturn($profile);

        $exporter = new ProfileExporter($profiler);

        self::assertSame(
            ['missing' => ['error' => 'Collector not found']],
            $exporter->exportCollectorsTool('tok-2', ['missing'])
        );
    }

    public function testExportCollectorsToolReturnsCollectorData(): void
    {
        $profile = new Profile('tok-3');

        $collector = new class() implements DataCollectorInterface, ExtractableDataCollectorInterface {
            public function extractData(): array|Data
            {
                return ['source' => 'extractable'];
            }

            public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
            {
            }

            public function getName(): string
            {
                return 'custom';
            }

            public function reset(): void
            {
            }
        };

        $profile->addCollector($collector);

        $profiler = $this->createMock(Profiler::class);
        $profiler
            ->expects(self::once())
            ->method('loadProfile')
            ->with('tok-3')
            ->willReturn($profile);

        $exporter = new ProfileExporter($profiler);
        $result = $exporter->exportCollectorsTool('tok-3', ['custom']);

        self::assertSame($collector::class, $result['custom']['class']);
        self::assertSame(['source' => 'extractable'], $result['custom']['data']);
        self::assertArrayNotHasKey('error', $result['custom']);
    }
}
