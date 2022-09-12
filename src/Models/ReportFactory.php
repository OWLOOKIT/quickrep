<?php
namespace Owlookit\Quickrep\Models;

use Owlookit\Quickrep\Http\Requests\QuickrepRequest;
use Owlookit\Quickrep\Interfaces\CacheInterface;
use Owlookit\Quickrep\Services\SocketService;
use Illuminate\Http\Request;

class ReportFactory
{
    /**
     * @param $reportClass
     * @param QuickrepRequest $request
     * @return QuickrepReport
     *
     * Build a QuickrepReport from a report class (string) and a request object.
     *
     */
    public static function build( $reportClass, QuickrepRequest $request ) : QuickrepReport
    {
        $parameters = ( $request->parameters == "" ) ? [] : explode("/", $request->parameters );

        // The code is the first parameter, saved on it's own for convenience
        $code = null;
        if ( count( $parameters ) > 0) {
            $code = array_shift($parameters);
        }

        // Request form input is non-cacheable input, aux parameters
        $request_form_input = json_decode(json_encode($request->all()),true);
        if ( !is_array( $request_form_input ) )  {
            $request_form_input = [];
        }

        $reportObject = new $reportClass( $code, $parameters, $request_form_input);

        // Call GetSQL() in order to initilaize socket-wrench system for UI
        $reportObject->GetSQL();

        return $reportObject;
    }
}
