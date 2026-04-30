<?php

declare(strict_types=1);

namespace Owlookit\Quickrep\Services;

use Carbon\Carbon;
use Owlookit\Quickrep\Interfaces\PreparedSourceReport;
use Owlookit\Quickrep\DTO\PreparedSourceStatus;
use Owlookit\Quickrep\Models\QuickrepPreparedSource;

final class PreparedSourceStatusResolver
{
    public function resolveForReport(object $report): ?PreparedSourceStatus
    {
        if (! config('quickrep.PREPARED_SOURCE_STATUS_ENABLED', true)) {
            return null;
        }

        if (! $report instanceof PreparedSourceReport) {
            return null;
        }

        $reportKey = $report->preparedSourceKey();

        $source = QuickrepPreparedSource::query()
            ->enabled()
            ->forReportKey($reportKey)
            ->first();

        if ($source === null) {
            return $this->fallbackFromReportContract($report);
        }

        return $this->fromModel($source);
    }

    private function fallbackFromReportContract(PreparedSourceReport $report): PreparedSourceStatus
    {
        $expectedFreshness = $report->preparedSourceExpectedFreshnessSeconds();
        $staleAfter = $report->preparedSourceStaleAfterSeconds();

        return new PreparedSourceStatus(
            reportKey: $report->preparedSourceKey(),
            reportClass: get_class($report),
            sourceConnection: $report->preparedSourceConnection(),
            sourceName: $report->preparedSourceName(),
            sourceType: 'unknown',
            refreshStrategy: 'unknown',
            refreshCommand: null,
            refreshCronExpression: null,
            refreshIntervalSeconds: null,
            refreshScheduleDescription: null,
            expectedFreshnessSeconds: $expectedFreshness,
            staleAfterSeconds: $staleAfter,
            lastRefreshStartedAt: null,
            lastRefreshFinishedAt: null,
            lastSuccessfulRefreshAt: null,
            lastRefreshDurationMs: null,
            lastRefreshStatus: 'not_registered',
            lastRefreshError: null,
            lastSourceRowCount: null,
            cacheConnection: quickrep_cache_db(),
            cacheTtlSeconds: null,
            lastCacheBuiltAt: null,
            lastCacheClearedAt: null,
            isStale: true,
            ageSeconds: null,
            freshnessStatus: 'not_registered',
            freshnessLabel: 'Источник отчёта не зарегистрирован',
            refreshLockOwner: null,
            refreshLockExpiresAt: null,
            lastRefreshTriggeredBy: null,
            isRefreshRunning: false,
        );
    }

    private function fromModel(QuickrepPreparedSource $source): PreparedSourceStatus
    {
        $lastSuccess = $source->last_successful_refresh_at;
        $now = Carbon::now();

        $ageSeconds = $lastSuccess !== null
            ? $lastSuccess->diffInSeconds($now)
            : null;

        $staleAfterSeconds = (int) $source->stale_after_seconds;

        $isStale = $ageSeconds === null || $ageSeconds > $staleAfterSeconds;
        $freshnessStatus = $this->freshnessStatus((string) $source->last_refresh_status, $isStale, $ageSeconds);
        $freshnessLabel = $this->freshnessLabel($source, $freshnessStatus, $ageSeconds);

        return new PreparedSourceStatus(
            reportKey: (string) $source->report_key,
            reportClass: (string) $source->report_class,
            sourceConnection: (string) $source->source_connection,
            sourceName: (string) $source->source_full_name,
            sourceType: (string) $source->source_type,
            refreshStrategy: (string) $source->refresh_strategy,
            refreshCommand: $source->refresh_command,
            refreshCronExpression: $source->refresh_cron_expression,
            refreshIntervalSeconds: $source->refresh_interval_seconds,
            refreshScheduleDescription: $source->refresh_schedule_description,
            expectedFreshnessSeconds: (int) $source->expected_freshness_seconds,
            staleAfterSeconds: $staleAfterSeconds,
            lastRefreshStartedAt: $source->last_refresh_started_at?->toIso8601String(),
            lastRefreshFinishedAt: $source->last_refresh_finished_at?->toIso8601String(),
            lastSuccessfulRefreshAt: $source->last_successful_refresh_at?->toIso8601String(),
            lastRefreshDurationMs: $source->last_refresh_duration_ms,
            lastRefreshStatus: (string) $source->last_refresh_status,
            lastRefreshError: $source->last_refresh_error,
            lastSourceRowCount: $source->last_source_row_count,
            cacheConnection: $source->cache_connection,
            cacheTtlSeconds: $source->cache_ttl_seconds,
            lastCacheBuiltAt: $source->last_cache_built_at?->toIso8601String(),
            lastCacheClearedAt: $source->last_cache_cleared_at?->toIso8601String(),
            isStale: $isStale,
            ageSeconds: $ageSeconds,
            freshnessStatus: $freshnessStatus,
            freshnessLabel: $freshnessLabel,
            refreshLockOwner: $source->refresh_lock_owner,
            refreshLockExpiresAt: $source->refresh_lock_expires_at?->toIso8601String(),
            lastRefreshTriggeredBy: $source->last_refresh_triggered_by,
            isRefreshRunning: $source->isRefreshRunning(),
        );
    }

    private function freshnessStatus(string $lastRefreshStatus, bool $isStale, ?int $ageSeconds): string
    {
        if ($lastRefreshStatus === 'running') {
            return 'refreshing';
        }

        if ($lastRefreshStatus === 'failed') {
            return $ageSeconds === null ? 'failed_without_data' : 'failed_with_old_data';
        }

        if ($ageSeconds === null) {
            return 'never_refreshed';
        }

        return $isStale ? 'stale' : 'fresh';
    }

    private function freshnessLabel(QuickrepPreparedSource $source, string $freshnessStatus, ?int $ageSeconds): string
    {
        return match ($freshnessStatus) {
            'refreshing' => 'Идёт обновление источника данных',
            'failed_without_data' => 'Последнее обновление не удалось, рабочих данных ещё нет',
            'failed_with_old_data' => 'Последнее обновление не удалось, показаны ранее подготовленные данные',
            'never_refreshed' => 'Источник данных ещё не обновлялся',
            'stale' => $this->ageLabel($ageSeconds, 'Данные устарели, последнее успешное обновление'),
            default => $this->ageLabel($ageSeconds, 'Данные актуальны, последнее обновление'),
        };
    }

    private function ageLabel(?int $ageSeconds, string $prefix): string
    {
        if ($ageSeconds === null) {
            return $prefix . ': неизвестно';
        }

        if ($ageSeconds < 60) {
            return $prefix . ': меньше минуты назад';
        }

        if ($ageSeconds < 3600) {
            return $prefix . ': ' . intdiv($ageSeconds, 60) . ' мин. назад';
        }

        if ($ageSeconds < 86400) {
            return $prefix . ': ' . intdiv($ageSeconds, 3600) . ' ч. назад';
        }

        return $prefix . ': ' . intdiv($ageSeconds, 86400) . ' дн. назад';
    }
}