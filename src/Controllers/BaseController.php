<?php

namespace Frankkessler\Salesforce\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class BaseController extends Controller
{
    use DispatchesJobs, ValidatesRequests;
}
