<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 5/2/19
 * Time: 12:33 PM
 */

namespace Owlookit\Quickrep\Http\Controllers;

use Owlookit\Quickrep\Http\Requests\QuickrepRequest;
use Owlookit\Quickrep\Interfaces\QuickrepReportInterface;
use Owlookit\Quickrep\Models\QuickrepReport;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

abstract class AbstractWebController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param QuickrepReport $report
     * @return mixed
     *
     * Implemnt this method to do any modifications to the report at the controller level.
     * Any view variables you set here will be set on every report.
     */
    public abstract function onBeforeShown(QuickrepReportInterface $report);

    /**
     * @return mixed
     *
     * Implement this method to specify the blade view template to use
     */
    public abstract function getViewTemplate();

    /**
     * @return mixed
     *
     * Implement this method to specify the report URL path like /QuickrepCard or /QuickrepGraph
     */
    public abstract function getReportApiPrefix();


    /**
     * @return string
     *
     * Read the API prefix like `qrapi` from the quickrep config fil
     */
    public function getApiPrefix()
    {
        return api_prefix();
    }

    /**
     * @param QuickrepRequest $request
     * @return null
     *
     * Default method for displaying a QuickrepReqest
     * This method builds the report, builds the presenter and returns the view
     */
    public function show(QuickrepRequest $request)
    {
        $report = $request->buildReport();
        $this->onBeforeShown($report);
        return $this->buildView($report);
    }

    /**
     * @return View
     *
     * Make a view by composing the report with necessary data from child controller
     */
    public function buildView(QuickrepReportInterface $report)
    {
        // Auth stuff
        $user = Auth::guard()->user();
        if ($user) {
            // Since this is a custom owlookit column on the database for JWT, make sure the property is set,
            if (isset($user->last_token)) {
                $report->setToken($user->last_token);
            }
        }

        // Get the overall Quickrep API prefix /qrapi
        $report->pushViewVariable('api_prefix', $this->getApiPrefix());

        // Get the API prefix for this report's controller from child controller
        $report->pushViewVariable('report_api_prefix', $this->getReportApiPrefix());

        // Get the view template from the child controller
        $view_template = $this->getViewTemplate();

        // This function gets both view variables set on the report, and in the controller
        $view_varialbes = $report->getViewVariables();

        // Push all of our view variables on the template, including the report object itself
        $view_varialbes = array_merge($view_varialbes, ['report' => $report]);

        return view( $view_template, $view_varialbes );
    }
}
