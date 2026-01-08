<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Agent\Tool;

use Mcp\Capability\Attribute\McpTool;
use Milton\VibedebugBundle\DataCollector\DataExtractor\CollectorDataExtractorInterface;
use Milton\VibedebugBundle\DataCollector\DataExtractor\Extractor\ExceptionCollectorDataExtractor;
use Milton\VibedebugBundle\DataCollector\DataExtractor\Extractor\GenericCollectorDataExtractor;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\AI\Platform\Contract\JsonSchema\Attribute\With;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\ExceptionDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * @phpstan-type CollectorExport non-empty-array<string, array{
 *     class?: class-string<DataCollectorInterface>,
 *     data?: array<string, mixed>,
 *     error?: 'Collector not found'
 * }>
 * @phpstan-type ExceptionExport array{
 *     class: class-string<\Throwable>|string,
 *     message: string,
 *     trace: array<array<string, mixed>>,
 *     data: Data|null
 * }
 * @phpstan-type SummaryExport array{
 *     token: string,
 *     parent: ?string,
 *     children: array<string>,
 *     ip: ?string,
 *     method: ?string,
 *     url: ?string,
 *     time: int,
 *     status_code: int|null,
 *     exception: array<ExceptionExport>|null,
 *     collectors: list<string>
 * }
 * @phpstan-type ProfileLoadError array{error: string, token: string}
 */
#[AsTool(
    name: 'vibedebug_profiler_summary',
    description: 'Export a compact summary and collector list for a profiler token.',
    method: 'exportSummary', /* @see ProfileExporter::exportSummary() */
)]
#[AsTool(
    name: 'vibedebug_profiler_export_collectors',
    description: 'Export requested data collectors for a profiler token.',
    method: 'exportCollectors', /* @see ProfileExporter::exportCollectors() */
)]
final readonly class ProfileExporter
{
    public function __construct(
        private Profiler $profiler,
    ) {
    }

    /**
     * @return (SummaryExport|ProfileLoadError)
     */
    #[McpTool(
        name: 'vibedebug_profiler_summary',
        description: 'Export a compact summary and collector list for a profiler token.',
    )]
    public function exportSummary(#[With(minLength: 1)] string $token): array
    {
        $profile = $this->profiler->loadProfile($token);

        if (null === $profile) {
            return [
                'error' => sprintf('Profile "%s" not found.', $token),
                'token' => $token,
            ];
        }

        return [
            'token' => $profile->getToken(),
            'parent' => $profile->getParentToken(),
            'children' => array_map(static fn (Profile $child): string => $child->getToken(), $profile->getChildren()),
            'ip' => $profile->getIp(),
            'method' => $profile->getMethod(),
            'url' => $profile->getUrl(),
            'time' => $profile->getTime(),
            'status_code' => $profile->getStatusCode(),
            'exception' => $this->getProfileException($profile),
            'collectors' => array_keys($profile->getCollectors()),
        ];
    }

    /**
     * @param non-empty-list<string> $collectors
     *
     * @return CollectorExport|ProfileLoadError
     */
    #[McpTool(
        name: 'vibedebug_profiler_export_collectors',
        description: 'Export requested data collectors for a profiler token.',
    )]
    public function exportCollectors(
        #[With(minLength: 1)] string $token,
        #[With(minItems: 1)] array $collectors,
    ): array {
        $profile = $this->profiler->loadProfile($token);

        if (null === $profile) {
            return [
                'error' => sprintf('Profile "%s" not found.', $token),
                'token' => $token,
            ];
        }

        $result = [];

        foreach ($collectors as $collectorName) {
            if (!$profile->hasCollector($collectorName)) {
                $result[$collectorName]['error'] = 'Collector not found';
                continue;
            }

            $collector = $profile->getCollector($collectorName);

            $result[$collectorName] = [
                'class' => $collector::class,
                'data' => $this->getDataExtractor($collector)->extractData(),
            ];
        }

        return $result;
    }

    /**
     * @return array<ExceptionExport>
     */
    private function getProfileException(Profile $profile): array
    {
        if (!$profile->hasCollector('exception')) {
            return [];
        }

        $collector = $profile->getCollector('exception');
        /** @var ExceptionCollectorDataExtractor $extractor */
        $extractor = $this->getDataExtractor($collector);

        return $extractor->extractData();
    }

    private function getDataExtractor(DataCollectorInterface $collector): CollectorDataExtractorInterface
    {
        return match ($collector::class) {
            ExceptionDataCollector::class => ExceptionCollectorDataExtractor::for($collector),
            default => GenericCollectorDataExtractor::for($collector),
        };
    }
}
