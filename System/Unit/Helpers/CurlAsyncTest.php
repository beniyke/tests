<?php

declare(strict_types=1);

namespace Tests\System\Unit\Helpers;

use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Promise;
use Helpers\Http\Client\Response;

class CurlAsyncTest extends \Tests\TestCase
{
    public function test_async_returns_promise()
    {
        $curl = new Curl();
        $promise = $curl->get('https://example.com')->async();

        $this->assertInstanceOf(Promise::class, $promise);
    }

    public function test_async_wait_returns_response()
    {
        $curl = new Curl();
        $response = $curl->get('https://www.google.com')->async()->wait();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->httpCode());
        $this->assertTrue($response->ok());
    }

    public function test_async_multiple_requests()
    {
        $curl1 = new Curl();
        $curl2 = new Curl();

        $promise1 = $curl1->get('https://www.google.com')->async();
        $promise2 = $curl2->get('https://www.google.com')->async();

        $response1 = $promise1->wait();
        $response2 = $promise2->wait();

        $this->assertEquals(200, $response1->httpCode());
        $this->assertEquals(200, $response2->httpCode());
    }

    public function test_async_invalid_domain_returns_error_response()
    {
        $curl = new Curl();
        // Use a non-existent domain to trigger resolution failure
        $response = $curl->get('https://invalid.domain.fails.test.xyz_123')->async()->wait();

        $this->assertInstanceOf(Response::class, $response);
        // Status should be false or http code 0
        $this->assertTrue($response->failed());
        $this->assertEquals(0, $response->httpCode());
        $this->assertStringContainsString('Async request failed', $response->body() ?? $response->message());
    }
}
