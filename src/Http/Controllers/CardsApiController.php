<?php

namespace Owlookit\Quickrep\Http\Controllers;

use Owlookit\Quickrep\Http\Requests\CardsReportRequest;
use Owlookit\Quickrep\Http\Requests\QuickrepRequest;
use Owlookit\Quickrep\Models\DatabaseCache;
use Owlookit\Quickrep\Reports\Tabular\ReportGenerator;
use Owlookit\Quickrep\Reports\Tabular\ReportSummaryGenerator;

class CardsApiController
{
    public function index(QuickrepRequest $request)
    {
        $report = $request->buildReport();
        $cache = new DatabaseCache($report, quickrep_cache_db());
        $generator = new ReportGenerator($cache);
        return $generator->toJson();
    }

    public function summary(QuickrepRequest $request)
    {
        $report = $request->buildReport();
        // Wrap the report in cache
        $cache = new DatabaseCache($report, quickrep_cache_db());
        $generator = new ReportSummaryGenerator($cache);
        return $generator->toJson();
    }
}
