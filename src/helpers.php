<?php

use Carsguide\ApiWrapper\ApiWrapper;
use Carsguide\Auth\AuthManager;

if (!function_exists('api_wrapper')) {
    /**
     * Get the ApiWrapper instance
     *
     * @param  string  $api
     * @return \Carsguide\ApiWrapper\ApiWrapper
     */
    function api_wrapper($api)
    {
        return app(ApiWrapper::class)
            ->setApi($api)
            ->setBearerToken(getJwt($api));
    }
}

if (!function_exists('get_jwt')) {
    /**
     * Get JWT token for the provided api
     *
     * @param  string  $api
     * @return string
     */
    function get_jwt($api)
    {
        $response = app(AuthManager::class)
            ->setAudience($api)
            ->cache()
            ->getToken();

        if (!$response->success) {
            throw new \Exception($response->message, $response->status_code);
        }

        return $response->access_token;
    }
}
