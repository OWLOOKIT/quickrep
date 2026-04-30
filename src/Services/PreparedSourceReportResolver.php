<?php

declare(strict_types=1);

namespace Owlookit\Quickrep\Services;

use InvalidArgumentException;
use Owlookit\Quickrep\Interfaces\PreparedSourceReport;
use Owlookit\Quickrep\Models\QuickrepPreparedSource;

final class PreparedSourceReportResolver
{
    public function resolve(string $reportKey): PreparedSourceReport
    {
        $source = QuickrepPreparedSource::query()
            ->enabled()
            ->forReportKey($reportKey)
            ->first();

        if ($source === null) {
            throw new InvalidArgumentException(sprintf(
                'Prepared source report [%s] is not registered',
                $reportKey
            ));
        }

        $reportClass = (string) $source->report_class;

        if (! class_exists($reportClass)) {
            throw new InvalidArgumentException(sprintf(
                'Prepared source report class [%s] does not exist',
                $reportClass
            ));
        }

        $report = new $reportClass(null, [], []);

        if (! $report instanceof PreparedSourceReport) {
            throw new InvalidArgumentException(sprintf(
                'Report [%s] must implement [%s]',
                $reportClass,
                PreparedSourceReport::class
            ));
        }

        return $report;
    }
}