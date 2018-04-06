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
    'connection_name' => ['host' => 'example.com',
        'version' => 'v1'],
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
