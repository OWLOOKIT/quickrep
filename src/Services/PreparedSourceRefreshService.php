<?php

declare(strict_types=1);

namespace Owlookit\Quickrep\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Owlookit\Quickrep\Interfaces\RefreshablePreparedSourceReport;
use Owlookit\Quickrep\DTO\PreparedSourceRefreshContext;
use Owlookit\Quickrep\DTO\PreparedSourceRefreshResult;
use Owlookit\Quickrep\Models\QuickrepMeta;
use Owlookit\Quickrep\Models\QuickrepPreparedSource;
use Throwable;

final class PreparedSourceRefreshService
{
    public function refresh(
        RefreshablePreparedSourceReport $report,
        string $triggeredBy = 'manual',
        bool $force = false,
        bool $clearCacheAfterRefresh = true,
        int $lockTtlSeconds = 3600,
    ): PreparedSourceRefreshResult {
        $reportKey = $report->preparedSourceKey();
        $lockOwner = gethostname() . ':' . getmypid() . ':' . bin2hex(random_bytes(6));

        $source = $this->acquireLock($report, $lockOwner, $triggeredBy, $force, $lockTtlSeconds);

        if ($source === null) {
            return PreparedSourceRefreshResult::failed(
                sprintf('Prepared source [%s] is already refreshing', $reportKey)
            );
        }

        $startedAt = Carbon::now();

        try {
            $result = $report->refreshPreparedSource(
                new PreparedSourceRefreshContext(
                    reportKey: $reportKey,
                    triggeredBy: $triggeredBy,
                    force: $force,
                    clearCacheAfterRefresh: $clearCacheAfterRefresh,
                    lockOwner: $lockOwner,
                )
            );

            if (! $result->success) {
                $this->markFailed($source, $lockOwner, $startedAt, $result->message ?? 'Refresh failed');

                return $result;
            }

            $this->markSuccess($source, $lockOwner, $startedAt, $result);

            if ($clearCacheAfterRefresh) {
                $this->markQuickrepCacheStale($reportKey);
            }

            return $result;
        } catch (Throwable $exception) {
            $this->markFailed($source, $lockOwner, $startedAt, $exception->getMessage());

            throw $exception;
        }
    }

    private function acquireLock(
        RefreshablePreparedSourceReport $report,
        string $lockOwner,
        string $triggeredBy,
        bool $force,
        int $lockTtlSeconds,
    ): ?QuickrepPreparedSource {
        $connectionName = config('quickrep.QUICKREP_CONFIG_DB_CONNECTION', config('database.default'));

        return DB::connection($connectionName)->transaction(function () use (
            $report,
            $lockOwner,
            $triggeredBy,
            $force,
            $lockTtlSeconds
        ) {
            /** @var QuickrepPreparedSource|null $source */
            $source = QuickrepPreparedSource::query()
                ->where('report_key', $report->preparedSourceKey())
                ->lockForUpdate()
                ->first();

            if ($source === null) {
                $source = QuickrepPreparedSource::query()->create([
                    'report_key' => $report->preparedSourceKey(),
                    'report_class' => get_class($report),
                    'source_connection' => $report->preparedSourceConnection(),
                    'source_schema' => $this->extractSchema($report->preparedSourceName()),
                    'source_name' => $this->extractName($report->preparedSourceName()),
                    'source_type' => 'table',
                    'refresh_strategy' => 'manual',
                    'expected_freshness_seconds' => $report->preparedSourceExpectedFreshnessSeconds(),
                    'stale_after_seconds' => $report->preparedSourceStaleAfterSeconds(),
                    'last_refresh_status' => 'never',
                    'cache_connection' => quickrep_cache_db(),
                    'cache_ttl_seconds' => method_exists($report, 'howLongToCacheInSeconds')
                        ? (int) $report->howLongToCacheInSeconds()
                        : null,
                    'is_enabled' => true,
                ]);

                $source->refresh();
                $source->lockForUpdate();
            }

            if (! $force && $source->isRefreshRunning()) {
                return null;
            }

            $now = Carbon::now();

            $source->forceFill([
                'report_class' => get_class($report),
                'source_connection' => $report->preparedSourceConnection(),
                'source_schema' => $this->extractSchema($report->preparedSourceName()),
                'source_name' => $this->extractName($report->preparedSourceName()),
                'expected_freshness_seconds' => $report->preparedSourceExpectedFreshnessSeconds(),
                'stale_after_seconds' => $report->preparedSourceStaleAfterSeconds(),
                'last_refresh_started_at' => $now,
                'last_refresh_finished_at' => null,
                'last_refresh_status' => 'running',
                'last_refresh_error' => null,
                'refresh_lock_owner' => $lockOwner,
                'refresh_lock_expires_at' => $now->copy()->addSeconds($lockTtlSeconds),
                'last_refresh_triggered_by' => $triggeredBy,
                'updated_at' => $now,
            ])->save();

            return $source;
        });
    }

    private function markSuccess(
        QuickrepPreparedSource $source,
        string $lockOwner,
        Carbon $startedAt,
        PreparedSourceRefreshResult $result,
    ): void {
        $finishedAt = Carbon::now();

        QuickrepPreparedSource::query()
            ->whereKey($source->getKey())
            ->where('refresh_lock_owner', $lockOwner)
            ->update([
                'last_refresh_finished_at' => $finishedAt,
                'last_successful_refresh_at' => $finishedAt,
                'last_refresh_duration_ms' => $startedAt->diffInMilliseconds($finishedAt),
                'last_refresh_status' => 'success',
                'last_refresh_error' => null,
                'last_source_row_count' => $result->rowCount,
                'refresh_lock_owner' => null,
                'refresh_lock_expires_at' => null,
                'updated_at' => $finishedAt,
            ]);
    }

    private function markFailed(
        QuickrepPreparedSource $source,
        string $lockOwner,
        Carbon $startedAt,
        string $error,
    ): void {
        $finishedAt = Carbon::now();

        QuickrepPreparedSource::query()
            ->whereKey($source->getKey())
            ->where('refresh_lock_owner', $lockOwner)
            ->update([
                'last_refresh_finished_at' => $finishedAt,
                'last_refresh_duration_ms' => $startedAt->diffInMilliseconds($finishedAt),
                'last_refresh_status' => 'failed',
                'last_refresh_error' => mb_substr($error, 0, 10000),
                'refresh_lock_owner' => null,
                'refresh_lock_expires_at' => null,
                'updated_at' => $finishedAt,
            ]);
    }

    private function markQuickrepCacheStale(string $reportKey): void
    {
        QuickrepMeta::query()
            ->where('key', 'like', '%' . strtolower($reportKey) . '%')
            ->where('meta_key', 'created_at')
            ->delete();
    }

    private function extractSchema(string $sourceName): string
    {
        $parts = explode('.', $sourceName, 2);

        return count($parts) === 2 ? $parts[0] : 'application';
    }

    private function extractName(string $sourceName): string
    {
        $parts = explode('.', $sourceName, 2);

        return count($parts) === 2 ? $parts[1] : $sourceName;
    }
}