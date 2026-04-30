<?php

declare(strict_types=1);

namespace Owlookit\Quickrep\Console;

use Illuminate\Console\Command;
use Owlookit\Quickrep\Models\QuickrepPreparedSource;
use Owlookit\Quickrep\Services\PreparedSourceStatusResolver;
use Owlookit\Quickrep\Services\PreparedSourceReportResolver;

final class PreparedSourceStatusCommand extends Command
{
    protected $signature = 'quickrep:prepared-source:status
        {reportKey? : Prepared source report key}
        {--json : Output JSON}';

    protected $description = 'Show Quickrep prepared source freshness status';

    public function handle(
        PreparedSourceReportResolver $reportResolver,
        PreparedSourceStatusResolver $statusResolver,
    ): int {
        $reportKey = $this->argument('reportKey');

        if ($reportKey !== null) {
            $report = $reportResolver->resolve((string) $reportKey);
            $status = $statusResolver->resolveForReport($report);

            if ($this->option('json')) {
                $this->line(json_encode($status?->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                return self::SUCCESS;
            }

            $this->table(
                ['Key', 'Source', 'Status', 'Freshness', 'Last success', 'Rows'],
                [[
                    $status?->reportKey,
                    $status?->sourceName,
                    $status?->freshnessStatus,
                    $status?->freshnessLabel,
                    $status?->lastSuccessfulRefreshAt,
                    $status?->lastSourceRowCount,
                ]]
            );

            return self::SUCCESS;
        }

        $sources = QuickrepPreparedSource::query()
            ->enabled()
            ->orderBy('report_key')
            ->get();

        if ($this->option('json')) {
            $this->line($sources->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(
            ['Report key', 'Source', 'Refresh', 'Status', 'Last success', 'Rows'],
            $sources->map(fn (QuickrepPreparedSource $source): array => [
                $source->report_key,
                $source->source_schema . '.' . $source->source_name,
                $source->refresh_schedule_description ?? $source->refresh_cron_expression ?? $source->refresh_strategy,
                $source->last_refresh_status,
                optional($source->last_successful_refresh_at)->toDateTimeString(),
                $source->last_source_row_count,
            ])->all()
        );

        return self::SUCCESS;
    }
}