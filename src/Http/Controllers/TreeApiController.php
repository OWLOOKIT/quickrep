<?php

namespace Owlookit\Quickrep\Http\Controllers;

use Owlookit\Quickrep\Http\Requests\QuickrepRequest;
use Owlookit\Quickrep\Reports\Tree\CachedTreeReport;
use Owlookit\Quickrep\Reports\Tree\TreeReportGenerator;
use Owlookit\Quickrep\Reports\Tree\TreeReportSummaryGenerator;

class TreeApiController
{
    public function index( QuickrepRequest $request )
    {
        $report = $request->buildReport();
        $cache = new CachedTreeReport( $report, quickrep_cache_db() );
        $generator = new TreeReportGenerator( $cache );
        return $generator->toJson();
    }

    public function summary( QuickrepRequest $request )
    {
        $report = $request->buildReport();
        // Wrap the report in cache
        $cache = new CachedTreeReport( $report, quickrep_cache_db() );
        $generator = new TreeReportSummaryGenerator( $cache );
        return $generator->toJson();
    }
}
