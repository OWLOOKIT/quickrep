<?php

namespace Owlookit\Quickrep\Http\Controllers;

use Illuminate\Config\Repository;
use Owlookit\Quickrep\Http\Requests\CardsReportRequest;
use Owlookit\Quickrep\Interfaces\QuickrepReportInterface;

class CardController extends AbstractWebController
{
    /**
     * @return Repository|mixed
     *
     * Get the view template
     */
    public function getViewTemplate()
    {
        return config("quickrep.CARD_VIEW_TEMPLATE");
    }

    /**
     * @param $report
     *
     * Build presenter and push our required varialbes for this web view
     */
    public function onBeforeShown(QuickrepReportInterface $report)
    {
        $bootstrap_css_location = asset(config('quickrep.BOOTSTRAP_CSS_LOCATION', '/css/bootstrap.min.css'));
        $report->pushViewVariable('bootstrap_css_location', $bootstrap_css_location);
        $report->pushViewVariable('report_uri', $this->getReportUri($report));
        $report->pushViewVariable('summary_uri', $this->getSummaryUri($report));
    }

    /**
     * @param $report
     * @return string
     *
     * Protected method, specific to this controller, to build the report URI (though as of now it's the same as tabular)
     */
    protected function getReportUri($report)
    {
        $parameterString = implode("/", $report->getMergedParameters());
        $report_api_uri = "/{$this->getApiPrefix()}/{$this->getReportApiPrefix()}/{$report->uriKey()}/{$parameterString}";
        return $report_api_uri;
    }

    /**
     * @return string
     *
     * Specify the path to this report's API
     * This report uses the tabular api prefix
     */
    public function getReportApiPrefix()
    {
        return tabular_api_prefix();
    }

    protected function getSummaryUri($report)
    {
        $parameterString = implode("/", $report->getMergedParameters());
        $summary_api_uri = "/{$this->getApiPrefix()}/{$this->getReportApiPrefix()}/{$report->uriKey()}/Summary/{$parameterString}";
        return $summary_api_uri;
    }
}
