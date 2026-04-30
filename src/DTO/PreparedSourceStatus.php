<?php

declare(strict_types=1);

namespace Owlookit\Quickrep\DTO;

final readonly class PreparedSourceStatus
{
    public function __construct(
        public string $reportKey,
        public string $reportClass,
        public string $sourceConnection,
        public string $sourceName,
        public string $sourceType,
        public string $refreshStrategy,
        public ?string $refreshCommand,
        public ?string $refreshCronExpression,
        public ?int $refreshIntervalSeconds,
        public ?string $refreshScheduleDescription,
        public int $expectedFreshnessSeconds,
        public int $staleAfterSeconds,
        public ?string $lastRefreshStartedAt,
        public ?string $lastRefreshFinishedAt,
        public ?string $lastSuccessfulRefreshAt,
        public ?int $lastRefreshDurationMs,
        public string $lastRefreshStatus,
        public ?string $lastRefreshError,
        public ?int $lastSourceRowCount,
        public ?string $cacheConnection,
        public ?int $cacheTtlSeconds,
        public ?string $lastCacheBuiltAt,
        public ?string $lastCacheClearedAt,
        public bool $isStale,
        public ?int $ageSeconds,
        public string $freshnessStatus,
        public string $freshnessLabel,
    ) {
    }

    public function toArray(): array
    {
        return [
            'report_key' => $this->reportKey,
            'report_class' => $this->reportClass,
            'source_connection' => $this->sourceConnection,
            'source_name' => $this->sourceName,
            'source_type' => $this->sourceType,
            'refresh_strategy' => $this->refreshStrategy,
            'refresh_command' => $this->refreshCommand,
            'refresh_cron_expression' => $this->refreshCronExpression,
            'refresh_interval_seconds' => $this->refreshIntervalSeconds,
            'refresh_schedule_description' => $this->refreshScheduleDescription,
            'expected_freshness_seconds' => $this->expectedFreshnessSeconds,
            'stale_after_seconds' => $this->staleAfterSeconds,
            'last_refresh_started_at' => $this->lastRefreshStartedAt,
            'last_refresh_finished_at' => $this->lastRefreshFinishedAt,
            'last_successful_refresh_at' => $this->lastSuccessfulRefreshAt,
            'last_refresh_duration_ms' => $this->lastRefreshDurationMs,
            'last_refresh_status' => $this->lastRefreshStatus,
            'last_refresh_error' => $this->lastRefreshError,
            'last_source_row_count' => $this->lastSourceRowCount,
            'cache_connection' => $this->cacheConnection,
            'cache_ttl_seconds' => $this->cacheTtlSeconds,
            'last_cache_built_at' => $this->lastCacheBuiltAt,
            'last_cache_cleared_at' => $this->lastCacheClearedAt,
            'is_stale' => $this->isStale,
            'age_seconds' => $this->ageSeconds,
            'freshness_status' => $this->freshnessStatus,
            'freshness_label' => $this->freshnessLabel,
        ];
    }
}