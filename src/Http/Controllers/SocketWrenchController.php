<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 3/12/19
 * Time: 12:05 PM
 */

namespace Owlookit\Quickrep\Http\Controllers;


use Owlookit\Quickrep\Http\Requests\SocketWrenchRequest;
use Owlookit\Quickrep\Models\SocketUser;
use Owlookit\Quickrep\Models\Wrench;

class SocketWrenchController
{
    public function index( SocketWrenchRequest $request )
    {
        $socketUser = SocketUser::where( 'user_id', 1 )->first();
        $wrenches = Wrench::all();
        return $wrenches->toJson();
    }

    public function formSubmit( SocketWrenchRequest $request )
    {
        $socketUser = SocketUser::where( 'user_id', 1 )->first();
        $test = 0;
    }
}
