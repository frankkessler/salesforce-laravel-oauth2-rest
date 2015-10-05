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
        Authentication::returnAuthorizationLink();
    }

    public function process_authorization_callback(Request $request){
        if (!$request->has('code')){
            die;
        }
        Authentication::processAuthenicationCode($request->input('code'));
    }

   /* public function test_account(){
        $sf = new Salesforce();
        $account = $sf->getObject('001e000000fhzpb','Account');
        //$account = $sf->getObject('00Qe0000006a8x9','Lead');

        var_dump($account);
    }*/
}
