<?php

declare(strict_types=1);

use Helpers\Http\Client\Batch;
use Helpers\Http\Client\Curl;

describe('Batch', function () {

    describe('Instantiation', function () {
        test('creates batch with requests', function () {
            $batch = new Batch([]);
            expect($batch)->toBeInstanceOf(Batch::class);
        });

        test('creates batch with empty array', function () {
            $batch = new Batch([]);
            expect($batch)->toBeInstanceOf(Batch::class);
        });
    });

    describe('Callback Registration', function () {
        test('registers before callback', function () {
            $batch = new Batch([]);
            $called = false;
            $result = $batch->before(function () use (&$called) {
                $called = true;
            });

            expect($result)->toBeInstanceOf(Batch::class);
            expect($batch->getBeforeCallback())->toBeInstanceOf(Closure::class);
        });

        test('registers progress callback', function () {
            $batch = new Batch([]);
            $result = $batch->progress(function ($response, $key) {
                // Progress tracking
            });

            expect($result)->toBeInstanceOf(Batch::class);
            expect($batch->getProgressCallback())->toBeInstanceOf(Closure::class);
        });

        test('registers then callback', function () {
            $batch = new Batch([]);
            $result = $batch->then(function ($results) {
                // Success handler
            });

            expect($result)->toBeInstanceOf(Batch::class);
            expect($batch->getThenCallback())->toBeInstanceOf(Closure::class);
        });

        test('registers catch callback', function () {
            $batch = new Batch([]);
            $result = $batch->catch(function ($response, $key) {
                // Error handler
            });

            expect($result)->toBeInstanceOf(Batch::class);
            expect($batch->getCatchCallback())->toBeInstanceOf(Closure::class);
        });

        test('registers finally callback', function () {
            $batch = new Batch([]);
            $result = $batch->finally(function () {
                // Cleanup
            });

            expect($result)->toBeInstanceOf(Batch::class);
            expect($batch->getFinallyCallback())->toBeInstanceOf(Closure::class);
        });
    });

    describe('Callback Chaining', function () {
        test('chains multiple callbacks', function () {
            $batch = new Batch([]);
            $result = $batch
                ->before(fn () => null)
                ->progress(fn ($r, $k) => null)
                ->then(fn ($results) => null)
                ->catch(fn ($r, $k) => null)
                ->finally(fn () => null);

            expect($result)->toBeInstanceOf(Batch::class);
            expect($batch->getBeforeCallback())->toBeInstanceOf(Closure::class);
            expect($batch->getProgressCallback())->toBeInstanceOf(Closure::class);
            expect($batch->getThenCallback())->toBeInstanceOf(Closure::class);
            expect($batch->getCatchCallback())->toBeInstanceOf(Closure::class);
            expect($batch->getFinallyCallback())->toBeInstanceOf(Closure::class);
        });
    });

    describe('Stop on Failure', function () {
        test('catch callback enables stop on failure', function () {
            $batch = new Batch([]);
            $batch->catch(fn ($r, $k) => null);
            expect($batch->shouldStopOnFailure())->toBeTrue();
        });

        test('stop on failure is false by default', function () {
            $batch = new Batch([]);
            expect($batch->shouldStopOnFailure())->toBeFalse();
        });
    });

    describe('Request Management', function () {
        test('retrieves requests', function () {
            $requests = [
                'users' => (new Curl())->get('https://api.example.com/users'),
                'posts' => (new Curl())->get('https://api.example.com/posts'),
            ];
            $batch = new Batch($requests);

            $requests = $batch->getRequests();
            expect($requests)->toBeArray();
            expect($requests)->toHaveCount(2);
            expect($requests)->toHaveKey('users');
            expect($requests)->toHaveKey('posts');
        });

        test('requests are Curl instances', function () {
            $requests = [
                'users' => (new Curl())->get('https://api.example.com/users'),
                'posts' => (new Curl())->get('https://api.example.com/posts'),
            ];
            $batch = new Batch($requests);

            $requests = $batch->getRequests();
            expect($requests['users'])->toBeInstanceOf(Curl::class);
            expect($requests['posts'])->toBeInstanceOf(Curl::class);
        });
    });

    describe('Results Management', function () {
        test('retrieves empty results initially', function () {
            $batch = new Batch([]);
            $results = $batch->getResults();
            expect($results)->toBeArray();
            expect($results)->toHaveCount(0);
        });

        test('sets results', function () {
            $batch = new Batch([]);
            $mockResults = ['users' => 'result1', 'posts' => 'result2'];
            $batch->setResults($mockResults);

            $results = $batch->getResults();
            expect($results)->toBe($mockResults);
        });
    });

    describe('Failed Status', function () {
        test('has not failed by default', function () {
            $batch = new Batch([]);
            expect($batch->hasFailed())->toBeFalse();
        });

        test('sets failed status', function () {
            $batch = new Batch([]);
            $batch->setFailed(true);
            expect($batch->hasFailed())->toBeTrue();
        });

        test('clears failed status', function () {
            $batch = new Batch([]);
            $batch->setFailed(true);
            $batch->setFailed(false);
            expect($batch->hasFailed())->toBeFalse();
        });
    });

    describe('Send Method', function () {
        test('send returns self', function () {
            // Note: This will actually execute the batch, but with invalid URLs
            // it should still return the Batch instance
            $requests = [
                'users' => (new Curl())->get('https://api.example.com/users'),
            ];
            $batch = new Batch($requests);

            $result = $batch->send();
            expect($result)->toBeInstanceOf(Batch::class);
        });

        test('send can be chained', function () {
            $requests = [
                'users' => (new Curl())->get('https://api.example.com/users'),
            ];
            $batch = new Batch($requests);

            $result = $batch
                ->before(fn () => null)
                ->send();

            expect($result)->toBeInstanceOf(Batch::class);
        });
    });

    describe('Callback Getters', function () {
        test('returns null for unset callbacks', function () {
            $batch = new Batch([]);
            expect($batch->getBeforeCallback())->toBeNull();
            expect($batch->getProgressCallback())->toBeNull();
            expect($batch->getThenCallback())->toBeNull();
            expect($batch->getCatchCallback())->toBeNull();
            expect($batch->getFinallyCallback())->toBeNull();
        });

        test('returns closures for set callbacks', function () {
            $requests = [
                'users' => (new Curl())->get('https://api.example.com/users'),
                'posts' => (new Curl())->get('https://api.example.com/posts'),
            ];
            $batch = new Batch($requests);

            $batch
                ->before(fn () => null)
                ->progress(fn ($r, $k) => null)
                ->then(fn ($results) => null)
                ->catch(fn ($r, $k) => null)
                ->finally(fn () => null);

            expect($batch->getBeforeCallback())->toBeInstanceOf(Closure::class);
            expect($batch->getProgressCallback())->toBeInstanceOf(Closure::class);
            expect($batch->getThenCallback())->toBeInstanceOf(Closure::class);
            expect($batch->getCatchCallback())->toBeInstanceOf(Closure::class);
            expect($batch->getFinallyCallback())->toBeInstanceOf(Closure::class);
        });
    });

    describe('Complex Scenarios', function () {
        test('handles batch with single request', function () {
            $batch = new Batch([
                'users' => (new Curl())->get('https://api.example.com/users'),
            ]);

            expect($batch->getRequests())->toHaveCount(1);
        });

        test('handles batch with many requests', function () {
            $requests = [];
            for ($i = 1; $i <= 10; $i++) {
                $requests["request{$i}"] = (new Curl())->get("https://api.example.com/endpoint{$i}");
            }

            $batch = new Batch($requests);
            expect($batch->getRequests())->toHaveCount(10);
        });

        test('preserves request keys', function () {
            $requests = [
                'custom_key_1' => (new Curl())->get('https://api.example.com/1'),
                'custom_key_2' => (new Curl())->get('https://api.example.com/2'),
            ];

            $batch = new Batch($requests);
            $retrieved = $batch->getRequests();

            expect($retrieved)->toHaveKey('custom_key_1');
            expect($retrieved)->toHaveKey('custom_key_2');
        });
    });

    describe('Fluent Interface', function () {
        test('all methods return self for chaining', function () {
            $requests = [
                'users' => (new Curl())->get('https://api.example.com/users'),
            ];
            $batch = new Batch($requests);

            $result = $batch
                ->before(fn () => null)
                ->progress(fn ($r, $k) => null)
                ->then(fn ($results) => null)
                ->catch(fn ($r, $k) => null)
                ->finally(fn () => null);

            expect($result)->toBe($batch);
        });
    });
});
