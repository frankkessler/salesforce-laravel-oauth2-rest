<?php

namespace Frankkessler\Salesforce\Providers;

use Frankkessler\Salesforce\SalesforceConfig;
use Illuminate\Support\ServiceProvider;

class SalesforceLaravelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish your migrations
        $this->publishes([
            __DIR__.'/../../migrations/salesforce.php' => base_path('/database/migrations/2015_09_18_141101_create_salesforce_tokens_table.php'),
        ], 'migrations');

        //publish config
        $this->publishes([
            __DIR__.'/../../config/salesforce.php' => config_path('salesforce.php'),
        ], 'config');

        //merge default config if values were removed or never published
        $this->mergeConfigFrom(__DIR__.'/../../config/salesforce.php', 'salesforce');

        //set custom package views folder
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'salesforce');

        //set custom routes for admin pages
        if (SalesforceConfig::get('salesforce.enable_oauth_routes')) {
            if (!$this->app->routesAreCached()) {
                require __DIR__.'/../../http/routes.php';
            }
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('salesforce', function ($app) {
            return $app->make('Frankkessler\Salesforce\Salesforce', [
                'config' => [
                    'salesforce.logger' => $app['log'],
                ],
            ]);
        });
    }
}
