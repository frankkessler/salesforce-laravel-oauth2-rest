<?php

namespace Frankkessler\Salesforce\Controllers;

use Frankkessler\Salesforce\Authentication;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

        return Authentication::processAuthenticationCode($request->input('code'));
    }
}
