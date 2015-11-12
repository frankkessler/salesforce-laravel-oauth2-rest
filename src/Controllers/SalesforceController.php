<?php

namespace Frankkessler\Salesforce\Controllers;

use Illuminate\Http\Request;

use App;

use App\Http\Requests;
use Frankkessler\Salesforce\Controllers\BaseController;
use Illuminate\Support\Facades\View;
use Frankkessler\Salesforce\Authentication;

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

    public function process_authorization_callback(Request $request){
        if (!$request->has('code')){
            die;
        }
        return Authentication::processAuthenicationCode($request->input('code'));
    }
}
