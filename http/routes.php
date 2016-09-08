<?php

\Route::group(['middleware' => 'auth'], function () {
    \Route::get('salesforce/admin/login', 'Frankkessler\Salesforce\Controllers\SalesforceController@login_form');
    \Route::get('salesforce/admin/callback', 'Frankkessler\Salesforce\Controllers\SalesforceController@process_authorization_callback');
    \Route::get('salesforce/admin/test', 'Frankkessler\Salesforce\Controllers\SalesforceController@test_account');
});
