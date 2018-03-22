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
    public function shouldLogSuccessTest()
    {
        Config::set('connections.host', ['host' => 'host', 'version' => 'v1']);

        Log::shouldReceive('info')->times(1);

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
        Config::set('connections.host', ['host' => 'host', 'version' => 'v1']);

        Log::shouldReceive('error')->once();

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
     *@test
     *
     */
    public function missingConnectionThrowsErrorTest()
    {
        $this->expectException(Exception::class);

        $this->buildRequest();
    }

    /**
     *@test
     *
     */
    public function missingConnectionHostThrowsErrorTest()
    {
        Config::set('connections.host', ['version' => 'v1']);

        $this->expectException(Exception::class);

        $this->buildRequest();
    }

    /**
     *@test
     *
     */
    public function missingConnectionVersionThrowsErrorTest()
    {
        Config::set('connections.host', ['host' => 'host']);

        $this->expectException(Exception::class);

        $this->buildRequest();
    }

    protected function make404Request()
    {
        Config::set('connections.host', ['host' => 'host', 'version' => 'v1']);

        $response = new Response(404, ['X-Foo' => 'Bar']);

        $this->makeRequest($response);

    }

    protected function makeValidRequest()
    {
        Config::set('connections.host', ['host' => 'host', 'version' => 'v1']);

        $response = new Response(200, ['X-Foo' => 'Bar']);

        $this->makeRequest($response);

    }

    protected function makeRequest($response)
    {
        $encryptedNvic = 'nvic';

        $token = (object) ['access_token' => 'token'];

        $this->client->shouldReceive('request')
            ->andReturn($response);

        $this->service = new ApiWrapper($this->client);

        $this->service->setApi('host')
            ->setHeaderAuthorization('token')
            ->setRequestType('GET')
            ->setResource('/vehicles/nvic')
            ->buildRequest();
    }

    protected function buildRequest()
    {
        $encryptedNvic = 'nvic';

        $this->service = new ApiWrapper($this->client);
        $this->service->setApi('host')
            ->setHeaderAuthorization('token')
            ->setRequestType('GET')
            ->setResource('/vehicles/nvic')
            ->buildRequest();
    }

}
