<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 5/2/19
 * Time: 12:33 PM
 */

namespace Owlookit\Quickrep\Http\Controllers;


use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class AbstractApiController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
