<?php

namespace Carsguide\ApiWrapper;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class ApiWrapper
{
    /**
     * Api Audience
     *
     * @var string
     */
    protected $api;

    /**
     * Request timeout
     *
     * @var int
     */
    public $timeout = 10;

    /**
     * Request type
     *
     * @var string
     */
    protected $type = 'POST';

    /**
     * Error message for when the connection config can't be found
     *
     */
    const MISSING_CONNECTION_ERROR = 'Missing connection config';

    /**
     * Request headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Http body content
     *
     * @var string
     */
    protected $body = '';

    /**
     * Query params
     *
     * @var array
     */
    protected $queryParams = [];

    /**
     * Set the client and Authentication Manager
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Set request resource
     *
     * @param string
     * @return ApiWrapper
     */
    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Set request api in the auth manager
     *
     * @param string
     * @return ApiWrapper
     */
    public function setApi($api)
    {
        $this->api = $api;

        return $this;
    }

    /**
     * Set Request type
     *
     * @param string
     * @return ApiWrapper
     */
    public function setRequestType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set access token
     *
     * @param string
     * @return ApiWrapper
     */
    public function setHeaderAuthorization($accessToken)
    {
        $this->headers['Authorization'] = $accessToken;

        return $this;
    }

    /**
     * Set bearer token
     *
     * @param string
     * @return ApiWrapper
     */
    public function setBearerToken($token)
    {
        $this->setHeaderAuthorization('Bearer ' . $token);

        return $this;
    }

    /**
     * Set request headers
     *
     * @param array
     * @return ApiWrapper
     */
    public function setHeaders($headers)
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * Set http body
     *
     * @param array $body
     * @return ApiWrapper
     */
    public function setBody($body)
    {
        $this->body = json_encode($body);

        return $this;
    }

    /**
     * Set query params
     *
     * @param array $params
     * @return ApiWrapper
     */
    public function setQueryParams(array $params)
    {
        $this->queryParams = $params;

        return $this;
    }

    /**
     * Set http multipart
     *
     * @param array $multipart
     * @return ApiWrapper
     */
    public function setMultipart($multipart)
    {
        $this->multipart = $multipart;

        return $this;
    }

    /**
     * Build request
     * Connection variables are found using the api
     *
     * @param array $requestOptions
     * @return ApiWrapper
     * @throws Exception
     */
    protected function buildRequest($requestOptions = [])
    {
        $connection = $this->getConnection();

        $this->setRequestOptions($requestOptions);

        $this->url = $connection['host'] . '/api/' . $connection['version'] . $this->resource;

        return $this;
    }

    /**
     * Get connection configuration
     *
     * @return array
     * @throws Exception
     */
    protected function getConnection()
    {
        $connection = config('connections.' . $this->api);

        if (!$connection || empty($connection['host']) || empty($connection['version'])) {
            throw new Exception(static::MISSING_CONNECTION_ERROR);
        }
        return $connection;
    }

    /**
     * Build the array of request options setting the timeout and authorization header
     *
     * @param array $requestOptions
     * @return void
     */
    public function setRequestOptions($requestOptions = [])
    {
        $this->requestOptions = array_merge([
            'timeout' => $this->timeout,
            'headers' => $this->headers,
        ], $requestOptions);

        if (!empty($this->body)) {
            $this->requestOptions['body'] = $this->body;
        }

        if (count($this->queryParams)) {
            $this->requestOptions['query'] = $this->queryParams;
        }

        if (!empty($this->multipart)) {
            $this->requestOptions['multipart'] = $this->multipart;
        }
    }

    /**
     * Set json header
     *
     * @return void
     */
    protected function setJsonHeader()
    {
        if (!isset($this->headers['content-type'])) {
            $this->setHeaders(['content-type' => 'application/json']);
        }
    }

    /**
     * Request data via get method
     *
     * @param string $endpoint
     * @param array $params
     * @param array $requestOptions
     * @return ResponseInterface $response
     * @throws GuzzleException
     */
    public function get(string $endpoint, array $params = [], array $requestOptions = [])
    {
        $this->setQueryParams($params);

        return $this->request('GET', $endpoint, $requestOptions);
    }

    /**
     * Communicate api via post method
     *
     * @param string $endpoint
     * @param array $data
     * @param array $requestOptions
     * @return ResponseInterface $response
     * @throws GuzzleException
     */
    public function post(string $endpoint, array $data, array $requestOptions = [])
    {
        $this->setJsonHeader();

        $this->setBody($data);

        return $this->request('POST', $endpoint, $requestOptions);
    }

    /**
     * Communicate api via post method with a content type of multipart
     *
     * @param string $endpoint
     * @param array $data
     * @param array $requestOptions
     * @return ResponseInterface $response
     * @throws GuzzleException
     */
    public function postMultipart(string $endpoint, array $data, array $requestOptions = [])
    {
        $this->setMultipart($data);

        return $this->request('POST', $endpoint, $requestOptions);
    }

    /**
     * Communicate api via put method
     *
     * @param string $endpoint
     * @param array $data
     * @param array $requestOptions
     * @return ResponseInterface $response
     * @throws GuzzleException
     */
    public function put(string $endpoint, array $data, array $requestOptions = [])
    {
        $this->setJsonHeader();

        $this->setBody($data);

        return $this->request('PUT', $endpoint, $requestOptions);
    }

    /**
     * Communicate api via patch method
     *
     * @param string $endpoint
     * @param array $data
     * @param array $requestOptions
     * @return ResponseInterface $response
     * @throws GuzzleException
     */
    public function patch(string $endpoint, array $data, array $requestOptions = [])
    {
        $this->setJsonHeader();

        $this->setBody($data);

        return $this->request('PATCH', $endpoint, $requestOptions);
    }

    /**
     * Delete request
     *
     * @param string $endpoint
     * @param array $params
     * @param array $requestOptions
     * @return ResponseInterface $response
     * @throws GuzzleException
     */
    public function delete(string $endpoint, array $params = [], array $requestOptions = [])
    {
        $this->setQueryParams($params);

        return $this->request('DELETE', $endpoint, $requestOptions);
    }

    /**
     * Build and send api request
     *
     * @param string $httpMethod
     * @param string $endpoint
     * @param array $requestOptions
     * @return ResponseInterface $response
     * @throws GuzzleException
     */
    protected function request(string $httpMethod, string $endpoint, array $requestOptions = [])
    {
        return $this->setRequestType($httpMethod)
            ->setResource($endpoint)
            ->setHeaders($this->headers)
            ->makeRequest($requestOptions);
    }

    /**
     * Send request
     *
     * @param $requestOptions
     * @return ResponseInterface $response
     * @throws GuzzleException
     * @throws Exception
     */
    public function makeRequest($requestOptions = [])
    {
        $this->buildRequest($requestOptions);

        $this->response = $this->client->request($this->type, $this->url, $this->requestOptions);

        return $this->response;
    }

    /**
     * Return body as a collection
     *
     * @return collection
     * @throws Exception
     */
    public function decodeBody()
    {
        if (empty($this->response)) {
            throw new Exception('Response not found');
        }

        return collect(json_decode($this->response->getBody(), true));
    }
}
