<?php

namespace Frankkessler\Salesforce\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use DispatchesJobs, ValidatesRequests;
}
