<?php

declare(strict_types=1);

namespace Owlookit\Quickrep\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Owlookit\Quickrep\Interfaces\RefreshablePreparedSourceReport;
use Owlookit\Quickrep\Models\QuickrepPreparedSource;
use Owlookit\Quickrep\Services\PreparedSourceRefreshService;
use Owlookit\Quickrep\Services\PreparedSourceReportResolver;

final class PreparedSourceRefreshStaleCommand extends Command
{
    protected $signature = 'quickrep:prepared-source:refresh-stale
        {--force : Refresh all enabled sources}
        {--triggered-by=scheduler : Trigger source label}
        {--lock-ttl=3600 : Lock TTL in seconds}';

    protected $description = 'Refresh stale Quickrep prepared sources';

    public function handle(
        PreparedSourceReportResolver $reportResolver,
        PreparedSourceRefreshService $refreshService,
    ): int {
        $now = Carbon::now();

        $sources = QuickrepPreparedSource::query()
            ->enabled()
            ->orderBy('report_key')
            ->get();

        $refreshed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($sources as $source) {
            $isStale = $source->last_successful_refresh_at === null
                || $source->last_successful_refresh_at->copy()->addSeconds((int) $source->stale_after_seconds)->lte($now);

            if (! $this->option('force') && ! $isStale) {
                $skipped++;
                continue;
            }

            try {
                $report = $reportResolver->resolve((string) $source->report_key);

                if (! $report instanceof RefreshablePreparedSourceReport) {
                    $this->warn(sprintf(
                        'Skipping [%s], report is not refreshable',
                        $source->report_key
                    ));
                    $skipped++;
                    continue;
                }

                $result = $refreshService->refresh(
                    report: $report,
                    triggeredBy: (string) $this->option('triggered-by'),
                    force: (bool) $this->option('force'),
                    clearCacheAfterRefresh: true,
                    lockTtlSeconds: (int) $this->option('lock-ttl'),
                );

                if ($result->success) {
                    $refreshed++;
                } else {
                    $failed++;
                    $this->error(sprintf('[%s] %s', $source->report_key, $result->message ?? 'failed'));
                }
            } catch (\Throwable $exception) {
                $failed++;
                $this->error(sprintf('[%s] %s', $source->report_key, $exception->getMessage()));
            }
        }

        $this->info(sprintf(
            'Done. refreshed=%d skipped=%d failed=%d',
            $refreshed,
            $skipped,
            $failed
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}