<?php

namespace Carsguide\ApiWrapper\Providers;

use Carsguide\ApiWrapper\ApiWrapper;
use Carsguide\Auth\AuthManager;
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
            return new ApiWrapper(new AuthManager(new Client()));
        });
    }
}
