<?php

declare(strict_types=1);

use Testing\Fakes\HttpFake;

beforeEach(function () {
    $this->http = new HttpFake();
});

describe('HttpFake - Stubbing', function () {
    test('returns default 200 response when no stubs', function () {
        $response = $this->http->get('https://api.example.com/users');

        expect($response['success'])->toBeTrue();
        expect($response['status'])->toBe(200);
    });

    test('can stub responses by URL', function () {
        $this->http->fake([
            'https://api.example.com/users' => [
                'status' => 200,
                'body' => ['users' => []],
            ],
        ]);

        $response = $this->http->get('https://api.example.com/users');

        expect($response['body'])->toBe(['users' => []]);
    });

    test('can stub with wildcards', function () {
        $this->http->fake([
            'https://api.example.com/*' => [
                'status' => 200,
                'body' => 'matched',
            ],
        ]);

        $response = $this->http->get('https://api.example.com/any/path');

        expect($response['body'])->toBe('matched');
    });

    test('can stub error responses', function () {
        $this->http->fake([
            'https://api.example.com/error' => [
                'status' => 500,
                'body' => 'Server Error',
            ],
        ]);

        $response = $this->http->get('https://api.example.com/error');

        expect($response['success'])->toBeFalse();
        expect($response['status'])->toBe(500);
    });
});

describe('HttpFake - HTTP Methods', function () {
    test('can make POST request', function () {
        $this->http->post('https://api.example.com/users', ['name' => 'John']);

        $requests = $this->http->recorded();
        expect($requests[0]['method'])->toBe('POST');
        expect($requests[0]['data']['name'])->toBe('John');
    });

    test('can make PUT request', function () {
        $this->http->put('https://api.example.com/users/1', ['name' => 'Jane']);

        $requests = $this->http->recorded();
        expect($requests[0]['method'])->toBe('PUT');
    });

    test('can make DELETE request', function () {
        $this->http->delete('https://api.example.com/users/1');

        $requests = $this->http->recorded();
        expect($requests[0]['method'])->toBe('DELETE');
    });

    test('can make PATCH request', function () {
        $this->http->patch('https://api.example.com/users/1', ['name' => 'Updated']);

        $requests = $this->http->recorded();
        expect($requests[0]['method'])->toBe('PATCH');
    });
});

describe('HttpFake - Headers', function () {
    test('can set headers for request', function () {
        $this->http->headers(['Authorization' => 'Bearer token'])
            ->get('https://api.example.com/protected');

        $requests = $this->http->recorded();
        expect($requests[0]['headers']['Authorization'])->toBe('Bearer token');
    });

    test('can use withHeader', function () {
        $this->http->withHeader('X-Custom', 'value')
            ->get('https://api.example.com/endpoint');

        $requests = $this->http->recorded();
        expect($requests[0]['headers']['X-Custom'])->toBe('value');
    });
});

describe('HttpFake - Assertions', function () {
    test('assertSent passes when request was made', function () {
        $this->http->get('https://api.example.com/users');

        $this->http->assertSent('https://api.example.com/users');
        expect(true)->toBeTrue();
    });

    test('assertSent with callback', function () {
        $this->http->post('https://api.example.com/users', ['name' => 'John']);

        $this->http->assertSent('https://api.example.com/users', function ($request) {
            return $request['data']['name'] === 'John';
        });
        expect(true)->toBeTrue();
    });

    test('assertSentWithMethod checks HTTP method', function () {
        $this->http->post('https://api.example.com/users', ['name' => 'John']);

        $this->http->assertSentWithMethod('POST', 'https://api.example.com/users');
        expect(true)->toBeTrue();
    });

    test('assertNotSent passes when URL was not called', function () {
        $this->http->get('https://api.example.com/users');

        $this->http->assertNotSent('https://api.example.com/products');
        expect(true)->toBeTrue();
    });

    test('assertSentCount checks total requests', function () {
        $this->http->get('https://example.com/1');
        $this->http->get('https://example.com/2');

        $this->http->assertSentCount(2);
        expect(true)->toBeTrue();
    });

    test('assertNothingSent passes when no requests made', function () {
        $this->http->assertNothingSent();
        expect(true)->toBeTrue();
    });
});

describe('HttpFake - Helper Methods', function () {
    test('recorded returns all requests', function () {
        $this->http->get('https://example.com/1');
        $this->http->get('https://example.com/2');

        expect($this->http->recorded())->toHaveCount(2);
    });

    test('clear removes all requests and stubs', function () {
        $this->http->fake(['https://example.com/*' => ['status' => 200]]);
        $this->http->get('https://example.com/1');

        $this->http->clear();

        expect($this->http->recorded())->toBeEmpty();
    });
});
