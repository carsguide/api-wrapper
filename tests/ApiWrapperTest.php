<?php

namespace Carsguide\Tests\ApiWrapper;

use Carsguide\ApiWrapper\ApiWrapper;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ApiWrapperTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar']),
            new Response(202, ['Content-Length' => 0]),
            new RequestException("Error Communicating with Server", new Request('GET', 'test')),
        ]);

        $handler = HandlerStack::create($mock);
        $this->client = new Client(['handler' => $handler]);
        $this->client = Mockery::mock(Client::class);
    }

    /**
     *@test
     *
     */
    public function missingConnectionThrowsErrorTest()
    {
        $this->expectException(Exception::class);

        $this->make404Request('missing_host');
    }

    /**
     *@test
     *
     */
    public function missingConnectionHostThrowsErrorTest()
    {
        Config::set('connections.example_host', ['version' => 'v1']);

        $this->expectException(Exception::class);

        $this->makeValidRequest('missing_host');

        Config::unset('connections.example_host');
    }

    /**
     *@test
     *
     */
    public function missingConnectionVersionThrowsErrorTest()
    {
        Config::set('connections.example_host', ['host' => 'host']);

        $this->expectException(Exception::class);

        $this->buildRequest();
    }

    /**
     * @test
     */
    public function shouldSetQueryParams()
    {
        $api = new ApiWrapper($this->client);

        $api->setQueryParams(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $this->getProtectedValue($api, 'queryParams'));
    }

    /**
     *@test
     *
     */
    public function shouldLogSuccessTest()
    {
        Config::set('connections.example_host', ['host' => 'host', 'version' => 'v1']);

        Log::shouldReceive('info')->twice();

        $this->makeValidRequest();

        $response = $this->service->makeRequest();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     *@test
     *
     */
    public function shouldLogFailureTest()
    {
        Config::set('connections.example_host', ['host' => 'host', 'version' => 'v1']);

        Log::shouldReceive('error')->twice();

        $this->make404Request();

        $response = $this->service->makeRequest();

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     *@test
     *
     */
    public function buildRequestFormatsCorrectTest()
    {
        $this->makeValidRequest();

        $this->assertEquals($this->service->url, "host/api/v1/vehicles/nvic");
    }

    /**
     *@test
     *
     */
    public function requestOptionsAreSetTest()
    {
        $this->makeValidRequest();

        $this->assertEquals($this->service->requestOptions['timeout'], 10);

        $this->assertEquals($this->service->requestOptions['headers']['Authorization'], 'token');
    }

    /**
     * @test
     */
    public function shouldAddQueryToRequestOptions()
    {
        $api = new ApiWrapper($this->client);

        $api->setQueryParams(['foo' => 'bar']);

        $this->invokeMethod($api, 'setRequestOptions');

        $this->assertCount(1, $api->requestOptions['query']);
    }

    /**
     *@test
     *
     */
    public function decodeBodyThrowsExceptionOnNullResponse()
    {
        $this->expectException(Exception::class);

        $this->service = new ApiWrapper($this->client);

        $this->service->decodeBody();
    }

    /**
     *@test
     *
     */
    public function decodeBodyWillReturnACollection()
    {
        Config::set('connections.example_host', ['host' => 'host', 'version' => 'v1']);

        $response = new Response(200, ['X-Foo' => 'Bar'], json_encode(['data' => 'first']));

        $this->makeRequest($response);

        $response = $this->service->decodeBody();

        $this->assertEquals('first', $response->first());
    }

    protected function make404Request($host = null)
    {
        Config::set('connections.example_host', ['host' => 'host', 'version' => 'v1']);

        $response = new Response(404, ['X-Foo' => 'Bar']);

        $this->makeRequest($response, $host);

    }

    protected function makeValidRequest($host = null)
    {
        Config::set('connections.example_host', ['host' => 'host', 'version' => 'v1']);

        $response = new Response(200, ['X-Foo' => 'Bar']);

        $this->makeRequest($response, $host);

    }

    protected function makeRequest($response, $api = 'example_host')
    {
        if (!$api) {
            $api = 'example_host';
        }
        $encryptedNvic = 'nvic';

        $token = (object) ['access_token' => 'token'];

        $this->client->shouldReceive('request')
            ->andReturn($response);

        $this->service = new ApiWrapper($this->client);

        $this->service->setApi($api)
            ->setHeaderAuthorization('token')
            ->setRequestType('GET')
            ->setResource('/vehicles/nvic')
            ->makeRequest();
    }

    protected function buildRequest($api = 'example_host')
    {
        $encryptedNvic = 'nvic';

        $this->service = new ApiWrapper($this->client);
        $this->service->setApi($api)
            ->setHeaderAuthorization('token')
            ->setRequestType('GET')
            ->setResource('/vehicles/nvic')
            ->makeRequest();
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Get protected property value
     *
     * @param $object
     * @param string $property
     * @return void
     */
    protected function getProtectedValue($object, $property)
    {
        $reflection = (new \ReflectionClass($object))->getProperty($property);

        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
