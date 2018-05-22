<?php
namespace Carsguide\ApiWrapper;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

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
     * Request success log message
     *
     */
    const SUCCESS_LOG = 'Successful request to service';

    /**
     * Request failure log message
     *
     */
    const API_FAILURE = 'Api request failed';

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
     * @param GuzzleHttp\Client $client
     * @return void
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
     * @return ApiWrapper
     */
    protected function buildRequest()
    {
        $connection = $this->getConnection();

        $this->setRequestOptions();

        $this->url = $connection['host'] . '/api/' . $connection['version'] . $this->resource;

        return $this;
    }

    /**
     * Get connection configuration
     *
     * @return array
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
     * @return void
     */
    protected function setRequestOptions()
    {
        $this->requestOptions = [
            'timeout' => $this->timeout,
            'headers' => $this->headers,
        ];

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
     * @return \Illuminate\Support\Collection
     */
    public function get(string $endpoint, array $params = [])
    {
        $this->setQueryParams($params);

        return $this->request('GET', $endpoint);
    }

    /**
     * Communicate api via post method
     *
     * @param string $endpoint
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    public function post(string $endpoint, array $data)
    {
        $this->setJsonHeader();

        $this->setBody($data);

        return $this->request('POST', $endpoint);
    }

    /**
     * Communicate api via post method with a content type of multipart
     *
     * @param string $endpoint
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    public function postMultipart(string $endpoint, array $data)
    {
        $this->setMultipart($data);

        return $this->request('POST', $endpoint);
    }

    /**
     * Communicate api via put method
     *
     * @param string $endpoint
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    public function put(string $endpoint, array $data)
    {
        $this->setJsonHeader();

        $this->setBody($data);

        return $this->request('PUT', $endpoint);
    }

    /**
     * Communicate api via patch method
     *
     * @param string $endpoint
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    public function patch(string $endpoint, array $data)
    {
        $this->setJsonHeader();

        $this->setBody($data);

        return $this->request('PATCH', $endpoint);
    }

    /**
     * Delete request
     *
     * @param string $endpoint
     * @param array $params
     * @return \Illuminate\Support\Collection
     */
    public function delete(string $endpoint, array $params = [])
    {
        $this->setQueryParams($params);

        return $this->request('DELETE', $endpoint);
    }

    /**
     * Build and send api request
     *
     * @param string $httpMethod
     * @param string $endpoint
     * @return \Illuminate\Support\Collection
     */
    protected function request(string $httpMethod, string $endpoint)
    {
        return $this->setRequestType($httpMethod)
            ->setResource($endpoint)
            ->setHeaders($this->headers)
            ->makeRequest();
    }

    /**
     * Send request
     *
     * @return \Psr\Http\Message\ResponseInterface $response
     */
    public function makeRequest()
    {
        $this->buildRequest();

        $this->response = $this->client->request($this->type, $this->url, $this->requestOptions);

        $responseCode = $this->response->getStatusCode();

        $successfulRequest = $responseCode == 200;

        if ($successfulRequest) {
            $this->logSuccess();
        } else {
            $this->logFailure($responseCode);
        }

        return $this->response;
    }

    /**
     * Return body as a collection
     *
     * @return collection
     */
    public function decodeBody()
    {
        if (empty($this->response)) {
            throw new Exception('Response not found');
        }

        return collect(json_decode($this->response->getBody(), true));
    }

    /**
     * Log success message
     * Called when the api response is 200
     *
     * @return void
     */
    protected function logSuccess()
    {
        Log::info(static::SUCCESS_LOG, [
            'api' => $this->api,
            'url' => $this->url,
        ]);
    }

    /**
     * Log a failure message
     * Called when the api response is not 200
     *
     * @param int $responseCode
     * @return void
     */
    protected function logFailure($responseCode)
    {
        Log::error(static::API_FAILURE, [
            'responseCode' => $responseCode,
            'url' => $this->url,
        ]);
    }

}
