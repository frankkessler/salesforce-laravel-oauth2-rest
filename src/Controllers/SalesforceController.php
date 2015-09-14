<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App;
use Config;
use App\Http\Requests;
use Frankkessler\Salesforce\Controllers\Controller;
use Illuminate\Support\Facades\View;


class ApiTokenManagementController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function login_form()
    {
        return view('salesforce_login');
    }
    public function login_form_submit(Request $request){
        if ($request->has('username') && $request->has('password')) {
            //try to do the salesforce oauth login
        }
    }
}
