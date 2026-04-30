<?php

declare(strict_types=1);

namespace Owlookit\Quickrep\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

final class QuickrepPreparedSource extends AbstractQuickrepModel
{
    protected $guarded = [];

    protected $casts = [
        'refresh_interval_seconds' => 'integer',
        'expected_freshness_seconds' => 'integer',
        'stale_after_seconds' => 'integer',
        'last_refresh_started_at' => 'datetime',
        'last_refresh_finished_at' => 'datetime',
        'last_successful_refresh_at' => 'datetime',
        'last_refresh_duration_ms' => 'integer',
        'last_source_row_count' => 'integer',
        'refresh_lock_expires_at' => 'datetime',
        'cache_ttl_seconds' => 'integer',
        'last_cache_built_at' => 'datetime',
        'last_cache_cleared_at' => 'datetime',
        'is_enabled' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('quickrep.PREPARED_SOURCES_TABLE', 'quickrep_prepared_sources');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForReportKey(Builder $query, string $reportKey): Builder
    {
        return $query->where('report_key', $reportKey);
    }

    public function sourceFullName(): Attribute
    {
        return Attribute::get(
            fn (): string => trim((string) $this->source_schema) . '.' . trim((string) $this->source_name)
        );
    }

    public function isRefreshRunning(): bool
    {
        return $this->last_refresh_status === 'running'
            && $this->refresh_lock_expires_at !== null
            && $this->refresh_lock_expires_at->isFuture();
    }

    public function isRefreshLockExpired(): bool
    {
        return $this->refresh_lock_expires_at === null
            || $this->refresh_lock_expires_at->isPast();
    }
}