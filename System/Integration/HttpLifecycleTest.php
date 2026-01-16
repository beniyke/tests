<?php

declare(strict_types=1);

/**
 * Integration tests for complete HTTP request lifecycle
 * Tests framework components work together
 */

use Database\Connection;
use Database\DB;
use Database\Schema\Schema;
use Helpers\Http\Response;

beforeEach(function () {
    $this->connection = Connection::configure('sqlite::memory:')
        ->name('test_http_lifecycle')
        ->connect();

    DB::setDefaultConnection($this->connection);
    Schema::setConnection($this->connection);
});

afterEach(function () {
    // Clean up
    if (isset($this->connection)) {
        $this->connection = null;
    }
});

describe('HTTP Request Lifecycle - MakesHttpRequests Trait', function () {
    test('it can handle a GET request', function () {
        $response = $this->get('/');

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->getStatusCode())->toBe(200);
    });


    test('it can handle a POST request with data', function () {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ];

        $response = $this->post('/submit', $data);

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->getStatusCode())->toBe(404);
    });
});

describe('HTTP Request Lifecycle - Database Integration', function () {
    test('database operations work in request context', function () {
        Schema::create('request_test', function ($table) {
            $table->id();
            $table->string('data');
        });

        DB::table('request_test')->insert(['data' => 'test']);
        $record = DB::table('request_test')->first();

        expect($record)->not->toBeNull();
        expect($record->data)->toBe('test');

        Schema::dropIfExists('request_test');
    });
});

describe('HTTP Request Lifecycle - Error Handling', function () {
    test('database errors are caught', function () {
        try {
            DB::table('non_existent')->get();
            $caught = false;
        } catch (Exception $e) {
            $caught = true;
        }

        expect($caught)->toBeTrue();
    });
});
