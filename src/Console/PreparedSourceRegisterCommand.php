<?php

declare(strict_types=1);

namespace Owlookit\Quickrep\Console;

use Illuminate\Console\Command;
use Owlookit\Quickrep\Interfaces\PreparedSourceReport;
use Owlookit\Quickrep\Models\QuickrepPreparedSource;
use Throwable;

final class PreparedSourceRegisterCommand extends Command
{
    protected $signature = 'quickrep:prepared-source:register
        {reportKey : Report class basename, for example AdminUsersAuthNetworksSchoolReport}
        {--class= : Fully-qualified report class. Defaults to App\\Reports\\{reportKey}}
        {--source-type=table : Prepared source type: table, view, materialized_view, continuous_aggregate}
        {--refresh-strategy=laravel_command : Refresh strategy: manual, laravel_command, pg_cron, external, none}
        {--refresh-interval= : Refresh interval in seconds}
        {--refresh-schedule= : Human-readable refresh schedule description}
        {--cache-ttl= : Quickrep cache TTL seconds}
        {--force : Update existing registry row}';

    protected $description = 'Register a Quickrep prepared source report in the prepared source registry';

    public function handle(): int
    {
        $reportKey = (string) $this->argument('reportKey');
        $reportClass = (string) ($this->option('class') ?: 'App\\Reports\\' . $reportKey);

        if (! class_exists($reportClass)) {
            $this->error(sprintf('Report class [%s] does not exist.', $reportClass));

            return self::FAILURE;
        }

        try {
            $report = new $reportClass(null, [], []);
        } catch (Throwable $exception) {
            $this->error(sprintf('Unable to instantiate report [%s]: %s', $reportClass, $exception->getMessage()));

            return self::FAILURE;
        }

        if (! $report instanceof PreparedSourceReport) {
            $this->error(sprintf(
                'Report [%s] must implement [%s].',
                $reportClass,
                PreparedSourceReport::class
            ));

            return self::FAILURE;
        }

        $actualReportKey = $report->preparedSourceKey();

        if ($actualReportKey !== $reportKey) {
            $this->warn(sprintf(
                'Argument reportKey [%s] differs from report preparedSourceKey() [%s]. Using [%s].',
                $reportKey,
                $actualReportKey,
                $actualReportKey
            ));
        }

        $sourceName = $report->preparedSourceName();
        [$sourceSchema, $sourceTable] = $this->splitSourceName($sourceName);

        $existing = QuickrepPreparedSource::query()
            ->where('report_key', $actualReportKey)
            ->first();

        if ($existing !== null && ! $this->option('force')) {
            $this->warn(sprintf(
                'Prepared source [%s] is already registered. Use --force to update it.',
                $actualReportKey
            ));

            return self::SUCCESS;
        }

        $cacheTtl = $this->option('cache-ttl');
        if ($cacheTtl === null && method_exists($report, 'howLongToCacheInSeconds')) {
            $cacheTtl = (int) $report->howLongToCacheInSeconds();
        }

        $refreshInterval = $this->option('refresh-interval');
        $refreshSchedule = $this->option('refresh-schedule');

        QuickrepPreparedSource::query()->updateOrCreate(
            ['report_key' => $actualReportKey],
            [
                'report_class' => $reportClass,
                'source_connection' => $report->preparedSourceConnection(),
                'source_schema' => $sourceSchema,
                'source_name' => $sourceTable,
                'source_type' => (string) $this->option('source-type'),
                'refresh_strategy' => (string) $this->option('refresh-strategy'),
                'refresh_command' => sprintf('quickrep:prepared-source:refresh %s', $actualReportKey),
                'refresh_interval_seconds' => $refreshInterval !== null
                    ? (int) $refreshInterval
                    : $report->preparedSourceExpectedFreshnessSeconds(),
                'refresh_schedule_description' => $refreshSchedule ?: $this->defaultScheduleDescription(
                    $report->preparedSourceExpectedFreshnessSeconds()
                ),
                'expected_freshness_seconds' => $report->preparedSourceExpectedFreshnessSeconds(),
                'stale_after_seconds' => $report->preparedSourceStaleAfterSeconds(),
                'cache_connection' => quickrep_cache_db(),
                'cache_ttl_seconds' => $cacheTtl !== null ? (int) $cacheTtl : null,
                'last_refresh_status' => $existing?->last_refresh_status ?: 'never',
                'is_enabled' => true,
            ]
        );

        $this->info(sprintf(
            'Prepared source report [%s] registered: %s.%s on connection [%s].',
            $actualReportKey,
            $sourceSchema,
            $sourceTable,
            $report->preparedSourceConnection()
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitSourceName(string $sourceName): array
    {
        $parts = explode('.', $sourceName, 2);

        if (count($parts) === 1) {
            return ['application', $parts[0]];
        }

        return [$parts[0], $parts[1]];
    }

    private function defaultScheduleDescription(int $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('Плановое обновление каждые %d сек.', $seconds);
        }

        if ($seconds < 3600) {
            return sprintf('Плановое обновление каждые %d мин.', intdiv($seconds, 60));
        }

        if ($seconds < 86400) {
            return sprintf('Плановое обновление каждые %d ч.', intdiv($seconds, 3600));
        }

        return sprintf('Плановое обновление каждые %d дн.', intdiv($seconds, 86400));
    }
}