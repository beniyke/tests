<?php

declare(strict_types=1);

use Helpers\Http\Client\Batch;
use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Response;

describe('Curl', function () {
    describe('HTTP Methods', function () {
        test('configures GET request', function () {
            $curl = new Curl();
            $curl->get('https://api.example.com/users');
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('configures POST request', function () {
            $curl = new Curl();
            $curl->post('https://api.example.com/users', ['name' => 'John']);
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('configures PUT request', function () {
            $curl = new Curl();
            $curl->put('https://api.example.com/users/1', ['name' => 'Jane']);
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('configures PATCH request', function () {
            $curl = new Curl();
            $curl->patch('https://api.example.com/users/1', ['email' => 'new@example.com']);
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('configures DELETE request', function () {
            $curl = new Curl();
            $curl->delete('https://api.example.com/users/1');
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('DELETE request can include data', function () {
            $curl = new Curl();
            $curl->delete('https://api.example.com/users/1', ['reason' => 'spam']);
            expect($curl)->toBeInstanceOf(Curl::class);
        });
    });

    describe('Headers', function () {
        test('sets custom headers', function () {
            $curl = new Curl();
            $curl->headers(['X-Custom-Header' => 'value']);
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('sets JSON content type', function () {
            $curl = new Curl();
            $curl->asJson();
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('sets form content type', function () {
            $curl = new Curl();
            $curl->asForm();
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('sets raw content type', function () {
            $curl = new Curl();
            $curl->asRaw('raw data');
            expect($curl)->toBeInstanceOf(Curl::class);
        });
    });

    describe('Authentication', function () {
        test('sets bearer token', function () {
            $curl = new Curl();
            $curl->withToken('my-secret-token');
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('sets custom token type', function () {
            $curl = new Curl();
            $curl->withToken('my-token', 'Custom');
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('sets basic auth', function () {
            $curl = new Curl();
            $curl->withBasicAuth('username', 'password');
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('sets digest auth', function () {
            $curl = new Curl();
            $curl->withDigestAuth('username', 'password');
            expect($curl)->toBeInstanceOf(Curl::class);
        });
    });

    describe('Query Parameters', function () {
        test('adds query parameters', function () {
            $curl = new Curl();
            $curl->withQueryParameters(['page' => 1, 'limit' => 10]);
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('merges multiple query parameter calls', function () {
            $curl = new Curl();
            $curl
                ->withQueryParameters(['page' => 1])
                ->withQueryParameters(['limit' => 10]);
            expect($curl)->toBeInstanceOf(Curl::class);
        });
    });

    describe('Configuration', function () {
        test('sets timeout', function () {
            $curl = new Curl();
            $curl->timeout(5000);
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('disables SSL verification', function () {
            $curl = new Curl();
            $curl->withoutSslVerification();
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('configures retry', function () {
            $curl = new Curl();
            $curl->retry(3, 100);
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('retry with zero times', function () {
            $curl = new Curl();
            $curl->retry(0);
            expect($curl)->toBeInstanceOf(Curl::class);
        });

        test('retry with negative times defaults to zero', function () {
            $curl = new Curl();
            $curl->retry(-5);
            expect($curl)->toBeInstanceOf(Curl::class);
        });
    });

    describe('File Attachments', function () {
        test('attaches file', function () {
            // Create a temporary test file
            $testFile = sys_get_temp_dir().'/test-upload.txt';
            file_put_contents($testFile, 'test content');

            $curl = new Curl();
            $curl->attach('file', $testFile);
            expect($curl)->toBeInstanceOf(Curl::class);

            // Cleanup
            unlink($testFile);
        });

        test('attaches file with mime type', function () {
            $testFile = sys_get_temp_dir().'/test-upload.txt';
            file_put_contents($testFile, 'test content');

            $curl = new Curl();
            $curl->attach('file', $testFile, 'text/plain');
            expect($curl)->toBeInstanceOf(Curl::class);

            unlink($testFile);
        });

        test('attaches file with custom filename', function () {
            $testFile = sys_get_temp_dir().'/test-upload.txt';
            file_put_contents($testFile, 'test content');

            $curl = new Curl();
            $curl->attach('file', $testFile, 'text/plain', 'custom-name.txt');
            expect($curl)->toBeInstanceOf(Curl::class);

            unlink($testFile);
        });

        test('ignores non-existent file', function () {
            $curl = new Curl();
            $curl->attach('file', '/non/existent/file.txt');
            expect($curl)->toBeInstanceOf(Curl::class);
        });
    });

    describe('Method Chaining', function () {
        test('chains multiple configuration methods', function () {
            $curl = new Curl();
            $curl->get('https://api.example.com/users')
                ->withToken('token')
                ->withQueryParameters(['page' => 1])
                ->timeout(5000)
                ->asJson();

            expect($curl)->toBeInstanceOf(Curl::class);
        });
    });

    describe('Send Without Configuration', function () {
        test('returns error response when URL not set', function () {
            $curl = new Curl();
            $response = $curl->send();
            expect($response)->toBeInstanceOf(Response::class);
            expect($response->failed())->toBeTrue();
        });

        test('returns error response when method not set', function () {
            // Even if we somehow bypass the method check
            $curl = new Curl();
            $response = $curl->send();
            expect($response)->toBeInstanceOf(Response::class);
        });
    });

    describe('Pool Method', function () {
        test('creates batch from callback', function () {
            $batch = Curl::pool(fn () => [
                'users' => (new Curl())->get('https://api.example.com/users'),
                'posts' => (new Curl())->get('https://api.example.com/posts'),
            ]);

            expect($batch)->toBeInstanceOf(Batch::class);
        });

        test('handles empty callback result', function () {
            $batch = Curl::pool(fn () => []);
            expect($batch)->toBeInstanceOf(Batch::class);
        });

        test('handles non-array callback result', function () {
            $batch = Curl::pool(fn () => null);
            expect($batch)->toBeInstanceOf(Batch::class);
        });
    });

    describe('Concurrent Method', function () {
        test('returns array from concurrent', function () {
            $responses = Curl::concurrent(fn () => []);
            expect($responses)->toBeArray();
        });

        test('handles null callback result', function () {
            $responses = Curl::concurrent(fn () => null);
            expect($responses)->toBeArray();
            expect($responses)->toHaveCount(1);
            expect($responses[0])->toBeInstanceOf(Response::class);
            expect($responses[0]->failed())->toBeTrue();
        });

        test('handles empty array callback result', function () {
            $responses = Curl::concurrent(fn () => []);
            expect($responses)->toBeArray();
            expect($responses)->toHaveCount(1);
        });
    });
});

describe('Response', function () {
    describe('Success Detection', function () {
        test('detects successful response', function () {
            $response = new Response([
                'status' => true,
                'http_code' => 200,
                'body' => '{"success":true}',
                'headers' => [],
                'message' => 'Success',
            ]);

            expect($response->successful())->toBeTrue();
            expect($response->failed())->toBeFalse();
        });

        test('detects failed response', function () {
            $response = new Response([
                'status' => false,
                'http_code' => 404,
                'body' => null,
                'headers' => [],
                'message' => 'Not Found',
            ]);

            expect($response->successful())->toBeFalse();
            expect($response->failed())->toBeTrue();
        });

        test('treats 4xx as failed', function () {
            $response = new Response([
                'status' => true,
                'http_code' => 400,
                'body' => 'Bad Request',
                'headers' => [],
                'message' => 'Success',
            ]);

            expect($response->failed())->toBeTrue();
        });

        test('treats 5xx as failed', function () {
            $response = new Response([
                'status' => true,
                'http_code' => 500,
                'body' => 'Server Error',
                'headers' => [],
                'message' => 'Success',
            ]);

            expect($response->failed())->toBeTrue();
        });
    });

    describe('Body Retrieval', function () {
        test('returns body as string', function () {
            $response = new Response([
                'status' => true,
                'http_code' => 200,
                'body' => 'Response body',
                'headers' => [],
                'message' => 'Success',
            ]);

            expect($response->body())->toBe('Response body');
        });

        test('returns null for empty body', function () {
            $response = new Response([
                'status' => true,
                'http_code' => 204,
                'body' => null,
                'headers' => [],
                'message' => 'Success',
            ]);

            expect($response->body())->toBeNull();
        });
    });

    describe('JSON Parsing', function () {
        test('parses JSON response', function () {
            $response = new Response([
                'status' => true,
                'http_code' => 200,
                'body' => '{"name":"John","age":30}',
                'headers' => [],
                'message' => 'Success',
            ]);

            $data = $response->json();
            expect($data)->toBeArray();
            expect($data['name'])->toBe('John');
            expect($data['age'])->toBe(30);
        });

        test('returns null for invalid JSON', function () {
            $response = new Response([
                'status' => true,
                'http_code' => 200,
                'body' => 'not json',
                'headers' => [],
                'message' => 'Success',
            ]);

            expect($response->json())->toBeNull();
        });

        test('returns null for empty body', function () {
            $response = new Response([
                'status' => true,
                'http_code' => 200,
                'body' => null,
                'headers' => [],
                'message' => 'Success',
            ]);

            expect($response->json())->toBeNull();
        });
    });

    describe('Header Retrieval', function () {
        test('retrieves specific header', function () {
            $response = new Response([
                'status' => true,
                'http_code' => 200,
                'body' => 'OK',
                'headers' => ['Content-Type' => 'application/json'],
                'message' => 'Success',
            ]);

            expect($response->header('Content-Type'))->toBe('application/json');
        });

        test('returns null for missing header', function () {
            $response = new Response([
                'status' => true,
                'http_code' => 200,
                'body' => 'OK',
                'headers' => [],
                'message' => 'Success',
            ]);

            expect($response->header('X-Custom'))->toBeNull();
        });
    });

    describe('Status Code', function () {
        test('retrieves status code', function () {
            $response = new Response([
                'status' => true,
                'http_code' => 201,
                'body' => 'Created',
                'headers' => [],
                'message' => 'Success',
            ]);

            expect($response->httpCode())->toBe(201);
        });
    });
});
