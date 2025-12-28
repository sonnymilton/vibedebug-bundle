<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle\Agent\Tool;

use Milton\VibedebugBundle\DataCollector\Adapter\GenericDataCollectorExtractorAdapter;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\AI\Platform\Contract\JsonSchema\Attribute\With;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\ExceptionDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * @phpstan-type CollectorExport non-empty-array<string, array{
 *     class?: class-string<DataCollectorInterface>,
 *     data?: array<mixed>|Data,
 *     error?: 'Collector not found'
 * }>
 * @phpstan-type SummaryExport array{
 *     token: string,
 *     parent: ?string,
 *     children: array<string>,
 *     ip: ?string,
 *     method: ?string,
 *     url: ?string,
 *     time: int,
 *     status_code: int|null,
 *     exception: FlattenException|null,
 *     collectors: list<string>
 * }
 * @phpstan-type ProfileLoadError array{error: string, token: string}
 */
#[AsTool(
    name: 'vibedebug_profiler_summary',
    description: 'Export a compact summary and collector list for a profiler token.',
    method: 'exportSummary',
)]
#[AsTool(
    name: 'vibedebug_profiler_export_collectors',
    description: 'Export requested data collectors for a profiler token.',
    method: 'exportCollectorsTool',
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
     * @param non-empty-list<string> $requestedCollectors
     **
     * @return CollectorExport|ProfileLoadError
     */
    public function exportCollectorsTool(
        #[With(minLength: 1)] string $token,
        #[With(minItems: 1)] array $requestedCollectors,
    ): array {
        $profile = $this->profiler->loadProfile($token);

        if (null === $profile) {
            return [
                'error' => sprintf('Profile "%s" not found.', $token),
                'token' => $token,
            ];
        }

        $result = [];

        foreach ($requestedCollectors as $collectorName) {
            if (!$profile->hasCollector($collectorName)) {
                $result[$collectorName]['error'] = 'Collector not found';
                continue;
            }

            $collector = $profile->getCollector($collectorName);

            $result[$collectorName] = [
                'class' => $collector::class,
                'data' => GenericDataCollectorExtractorAdapter::for($collector)->extractData(),
            ];
        }

        return $result;
    }

    private function getProfileException(Profile $profile): ?FlattenException
    {
        if (!$profile->hasCollector('exception')) {
            return null;
        }

        /** @var ExceptionDataCollector $collector */
        $collector = $profile->getCollector('exception');
        $exception = $collector->getException();

        if (!$exception instanceof FlattenException) {
            $exception = FlattenException::createWithDataRepresentation($exception);
        }

        return $exception;
    }
}
