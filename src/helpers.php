<?php

use Illuminate\Support\Facades\Config;
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
            ->setBearerToken(get_jwt($api));
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
        if (! Config::has("connections.{$api}.audience")) {
            throw new \Exception('Missing connection config');
        }

        $response = app(AuthManager::class)
            ->setAudience(Config::get("connections.{$api}.audience"))
            ->cache()
            ->getToken();

        if (!$response->success) {
            throw new \Exception($response->message, $response->status_code);
        }

        return $response->access_token;
    }
}

if (!function_exists('decode_body')) {
    /**
     * Decode the psr7 response
     *
     * @param  \GuzzleHttp\Psr7\Response  $response
     * @return \Illuminate\Support\Collection
     */
    function decode_body($response)
    {
        return collect(json_decode($response->getBody(), true));
    }
}
