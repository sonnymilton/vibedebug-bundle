<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Tests\DataCollector\DataExtractor\Extractor;

use Milton\VibedebugBundle\DataCollector\DataExtractor\Extractor\ExceptionCollectorDataExtractor;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use RuntimeException;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpKernel\DataCollector\ExceptionDataCollector;

final class ExceptionCollectorDataExtractorTest extends TestCase
{
    public function testExtractDataReturnsEmptyArrayWhenCollectorHasNoException(): void
    {
        $collector = new ExceptionDataCollector();

        $extractor = new ExceptionCollectorDataExtractor($collector);

        self::assertSame([], $extractor->extractData());
    }

    public function testExtractDataReturnsFlattenExceptionArrayWhenCollectorReturnsFlattenException(): void
    {
        $original = new RuntimeException('Boom');
        $flatten = FlattenException::create($original);

        $collector = $this->createMock(ExceptionDataCollector::class);
        $collector->method('getException')->willReturn($flatten);
        $collector->method('hasException')->willReturn(true);

        $extractor = new ExceptionCollectorDataExtractor($collector);

        self::assertSame($flatten->toArray(), $extractor->extractData());
    }

    public function testExtractDataConvertsThrowableToFlattenExceptionArray(): void
    {
        $original = new RuntimeException('Boom');

        $collector = $this->createMock(ExceptionDataCollector::class);
        $collector->method('getException')->willReturn($original);
        $collector->method('hasException')->willReturn(true);

        $extractor = new ExceptionCollectorDataExtractor($collector);

        $expected = FlattenException::create($original)->toArray();

        self::assertSame($expected, $extractor->extractData());
    }

}
