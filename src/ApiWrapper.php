<?php
namespace Carsguide\ApiWrapper;

use Carsguide\Auth\AuthManager;
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

    /**
     * Set the client and Authentication Manager
     *
     * @param GuzzleHttp\Client $client
     * @return void
     */
    public function __construct(AuthManager $manager, Client $client)
    {
        $this->client = $client;
        $this->auth = $manager;

    }

    /**
     * Set request resource
     *
     * @param string
     * @return ServiceManager
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
     * @return ServiceManager
     */
    public function setAudience($audience)
    {
        $this->auth->setAudience($audience);

        return $this;
    }

    /**
     * Set Request type
     *
     * @param string
     * @return ServiceManager
     */
    public function setRequestType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Build request
     * Connection variables are found using the audience
     *
     * @return ServiceManager
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
        $connection = config('connections.' . $this->auth->getAudience());

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
        $token = $this->auth->getToken();

        $this->requestOptions = [
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => $this->auth->getToken()->access_token,
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
            'audience' => $this->auth->getAudience(),
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
