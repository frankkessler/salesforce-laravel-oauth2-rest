<?php

namespace Chrisbjr\ApiGuard\Providers;

use Illuminate\Support\ServiceProvider;

class SalesforceLaravelServiceProvider extends ServiceProvider{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish your migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/salesforce.php' => base_path('/database/migrations/'.date('Y_m_d_His').'_create_salesforce_tokens_table.php')
        ], 'migrations');

        //publish config
        $this->publishes([
            __DIR__ . '/../../config/salesforce.php' => config_path('salesforce.php'),
        ], 'config');

        //merge default config if values were removed or never published
        $this->mergeConfigFrom(__DIR__.'/../../config/salesforce.php', 'salesforce');

        //set custom package views folder
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'salesforce');

        //set custom routes for admin pages
        if (! $this->app->routesAreCached()) {
            require __DIR__.'/../../../app/Http/routes.php';
            Route::get('salesforce/token',function(){

            });
            Route::group(['middleware' => 'auth'], function () {
                Route::get('salesforce_login', 'Frankkessler\Salesforce\SalesforceController@login_form');
                Route::post('salesforce_login', 'Frankkessler\Salesforce\SalesforceController@login_form_submit');
            });
        }
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app['salesforce'] = $this->app->share(function($app)
        {
            return $app->make('Frankkessler\Salesforce\Salesforce');
        });
    }

}