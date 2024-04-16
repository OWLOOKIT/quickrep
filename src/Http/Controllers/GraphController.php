<?php

namespace Owlookit\Quickrep\Http\Controllers;

use Owlookit\Quickrep\Http\Requests\GraphReportRequest;
use Owlookit\Quickrep\Interfaces\QuickrepReportInterface;

class GraphController extends AbstractWebController
{

    public function getViewTemplate()
    {
        return config("quickrep.GRAPH_VIEW_TEMPLATE", "");
    }

    /**
     * @param $report
     * @return void
     *
     * Push our graph URI variable onto the view
     */
    public function onBeforeShown(QuickrepReportInterface $report)
    {
        $bootstrap_css_location = asset(config('quickrep.BOOTSTRAP_CSS_LOCATION', '/css/bootstrap.min.css'));
        $report->pushViewVariable('bootstrap_css_location', $bootstrap_css_location);
        $report->pushViewVariable('graph_uri', $this->getGraphUri($report));
    }

    /**
     * @param $report
     * @return string
     *
     * Helper to assemble the graph URI for the report
     */
    public function getGraphUri($report)
    {
        $parameterString = implode("/", $report->getMergedParameters());
        $graph_api_uri = "/{$this->getApiPrefix()}/{$this->getReportApiPrefix()}/{$report->getClassName()}/{$parameterString}";
        $graph_api_uri = rtrim($graph_api_uri, '/'); //for when there is no parameterString
        return $graph_api_uri;
    }

    public function getReportApiPrefix()
    {
        return config('quickrep.GRAPH_API_PREFIX');
    }
}
