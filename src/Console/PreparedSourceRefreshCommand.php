<?php

declare(strict_types=1);

namespace Owlookit\Quickrep\Console;

use Illuminate\Console\Command;
use Owlookit\Quickrep\Interfaces\RefreshablePreparedSourceReport;
use Owlookit\Quickrep\Services\PreparedSourceRefreshService;
use Owlookit\Quickrep\Services\PreparedSourceReportResolver;

final class PreparedSourceRefreshCommand extends Command
{
    protected $signature = 'quickrep:prepared-source:refresh
        {reportKey : Prepared source report key}
        {--force : Force refresh even if lock exists}
        {--triggered-by=manual : Trigger source label}
        {--no-clear-cache : Do not clear/stale Quickrep cache after successful refresh}
        {--lock-ttl=3600 : Lock TTL in seconds}';

    protected $description = 'Refresh a Quickrep prepared source';

    public function handle(
        PreparedSourceReportResolver $reportResolver,
        PreparedSourceRefreshService $refreshService,
    ): int {
        $reportKey = (string) $this->argument('reportKey');
        $report = $reportResolver->resolve($reportKey);

        if (! $report instanceof RefreshablePreparedSourceReport) {
            $this->error(sprintf(
                'Report [%s] must implement [%s]',
                get_class($report),
                RefreshablePreparedSourceReport::class
            ));

            return self::FAILURE;
        }

        $this->info(sprintf('Refreshing prepared source [%s]...', $reportKey));

        $result = $refreshService->refresh(
            report: $report,
            triggeredBy: (string) $this->option('triggered-by'),
            force: (bool) $this->option('force'),
            clearCacheAfterRefresh: ! (bool) $this->option('no-clear-cache'),
            lockTtlSeconds: (int) $this->option('lock-ttl'),
        );

        if (! $result->success) {
            $this->error($result->message ?? 'Refresh failed');

            return self::FAILURE;
        }

        $this->info($result->message ?? 'Refresh completed');

        if ($result->rowCount !== null) {
            $this->line(sprintf('Rows: %d', $result->rowCount));
        }

        return self::SUCCESS;
    }
}