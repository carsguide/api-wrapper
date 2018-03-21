<?php
namespace Carsguide\ApiWrapper;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ApiWrapper
{

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

    protected $headers = [];

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
     * Set request Audience in the auth manager
     *
     * @param string
     * @return ApiWrapper
     */
    public function setAudience($audience)
    {
        $this->audience = $audience;

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
     * Set JWT access token
     *
     * @param string
     * @return ApiWrapper
     */
    public function setHeaderAuthorization($accessToken)
    {
        $this->headers['Authorization'] = $accessToken

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
        $this->headers = $headers;

        return $this;
    }

    /**
     * Build request
     * Connection variables are found using the audience
     *
     * @return ApiWrapper
     */
    public function buildRequest()
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
        $connection = config('connections.' . $this->audience);

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
            'headers' => [
                $this->headers,
            ],
        ];
    }

    /**
     * Send request
     *
     * @return \Psr\Http\Message\ResponseInterface $response
     */
    public function makeRequest()
    {
        $response = $this->client->request($this->type, $this->url, $this->requestOptions);

        $responseCode = $response->getStatusCode();

        $successfulRequest = $responseCode == 200;

        if ($successfulRequest) {
            $this->logSuccess();
        } else {
            $this->logFailure($responseCode);
        }

        return $response;
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
            'audience' => $this->audience,
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
