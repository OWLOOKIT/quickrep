<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 6/20/18
 * Time: 11:42 AM
 */

namespace Owlookit\Quickrep\Http\Controllers;

use Owlookit\Quickrep\Http\Requests\QuickrepRequest;
use Owlookit\Quickrep\Reports\Graph\CachedGraphReport;
use Owlookit\Quickrep\Reports\Graph\GraphGenerator;

class GraphApiController
{
    public function index( QuickrepRequest $request )
    {
        $report = $request->buildReport();

        // We use a subclass of the Standard DatabaseCache to enhance the functionality
        // To cache, not only the "main" table, but the node and link tables as well
        $cache = new CachedGraphReport( $report, quickrep_cache_db() );
        $generatorInterface = new GraphGenerator( $cache );
        return $generatorInterface->toJson();
    }
}
