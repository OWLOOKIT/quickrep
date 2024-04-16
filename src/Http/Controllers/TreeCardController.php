<?php

namespace Owlookit\Quickrep\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Owlookit\Quickrep\Http\Requests\CardsReportRequest;
use Owlookit\QuickrepBladeTreeCard\TreeCardPresenter;

class TreeCardController
{
    public function show(CardsReportRequest $request)
    {
        $presenter = new TreeCardPresenter($request->buildReport());

        $presenter->setApiPrefix(api_prefix());
        $presenter->setReportPath(tree_api_prefix());

        $user = Auth::guard()->user();
        if ($user) {
            $presenter->setToken($user->getRememberToken());
        }

        $view = config("quickrep.TREE_CARD_VIEW_TEMPLATE");

        return view($view, ['presenter' => $presenter]);
    }
}
