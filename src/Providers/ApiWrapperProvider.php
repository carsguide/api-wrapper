<?php

namespace Carsguide\ApiWrapper\Providers;

use Carsguide\ApiWrapper\ApiWrapper;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class ApiWrapperProvider extends ServiceProvider
{
    /**
     * Register auth service
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('apiwrapper', function () {
            return new ApiWrapper(new Client());
        });
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/connections.php', 'connections');
    }
}
