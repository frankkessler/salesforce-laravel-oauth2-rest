<?php

namespace Frankkessler\Salesforce\Controllers;

use Frankkessler\Salesforce\Authentication;
use Illuminate\Http\Request;

class SalesforceController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function login_form()
    {
        return Authentication::returnAuthorizationLink();
    }

    public function process_authorization_callback(Request $request)
    {
        if (!$request->has('code')) {
            die;
        }

        return Authentication::processAuthenicationCode($request->input('code'));
    }
}
