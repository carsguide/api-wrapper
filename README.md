# api-wrapper
Api wrapper for micro services

## Installation
Via composer
```
$ composer require carsguide/apiwrapper
```

## Configuration settings
Connections are added through a connections config file. 
To create a connection, add the config.connections file. Each item in the array points to another array containing the host and version of the api to be used.

```
return [
    'connection_name' => [
        'host' => 'example.com',
        'version' => 'v1',
        'audience' => 'Api Audience'
    ],
];
```

## Usage
### Create request
```php
$response = $this->api->setApi('vader')
        ->setRequestType('POST')
        ->setResource('/resource')
        ->setBody('body')
        ->setHeaders([
            'content-type' => 'application/json',
        ])
        ->setBearerToken($access_token)
        ->makeRequest();

$body = $this->api->decodeBody();
```
### Request via Get method
```php
    $response = $this->api->setApi($api)
        ->setBearerToken($access_token)
        ->get($endpoint);

    $body = json_decode($response->getBody());
```
### Request via helpers
```php
    $response = api_wrapper($api)->get($endpoint);

    $body = json_decode($response->getBody());
```
