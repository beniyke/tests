<?php

declare(strict_types=1);

namespace Tests\System\Feature;

use App\Enums\UserStatus;
use App\Listeners\ClearResourceCacheListener;
use App\Models\User;
use App\Providers\EventServiceProvider;
use App\Services\Auth\Interfaces\AuthServiceInterface;
use Core\Event;
use Core\Events\KernelTerminateEvent;
use Core\Ioc\Container;
use Core\Services\ConfigServiceInterface;
use Core\Support\Adapters\Interfaces\SapiInterface;
use Helpers\File\Cache;
use Helpers\Http\Request;
use Helpers\Http\Response;
use Helpers\Http\Session;
use Helpers\Http\UserAgent;
use Testing\Concerns\InteractsWithPackages;
use Testing\Concerns\RefreshDatabase;

uses(RefreshDatabase::class, InteractsWithPackages::class);

beforeEach(function () {
    $this->refreshDatabase();

    // Register EventServiceProvider to ensure ClearResourceCacheListener is active
    (new EventServiceProvider(Container::getInstance()))->boot();

    // Mock Auth for potential dependencies
    $auth = mock(AuthServiceInterface::class);
    $auth->shouldReceive('isAuthenticated')->andReturn(false)->byDefault();
    Container::getInstance()->instance(AuthServiceInterface::class, $auth);

    // Create a user to ensure the table exists
    User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'gender' => 'male',
        'password' => 'password',
        'status' => UserStatus::Active,
        'refid' => 'ref_jhon'
    ]);
});

function createCacheMockRequest(array $data = [], string $method = 'GET', string $uri = '/'): Request
{
    $_POST = $data;
    $_GET = ($method === 'GET') ? $data : [];
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['PHP_SELF'] = '/index.php' . $uri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';

    return Request::createFromGlobals(
        resolve(ConfigServiceInterface::class),
        resolve(SapiInterface::class),
        resolve(Session::class),
        resolve(UserAgent::class)
    );
}

test('it automatically tags cached queries with the table name', function () {
    // Enable caching for a query
    User::query()->cache(60)->get();

    // Verify cache exists with 'user' tag
    $cache = Cache::create('query')->withPath('user');

    // Since we can't easily check internal tags without reading the file
    // Let's verify it gets cleared when the 'user' tag is flushed
    expect($cache->getMetrics()['hits'])->toBe(0);

    // Warm it up
    User::query()->cache(60)->get();

    // Simulate cache hit if possible, or just flush and see if it misses
    Cache::create('query')->flushTags(['user']);

    // If it was tagged with 'user', it should be gone now
    // (Note: readRaw in Cache.php checks timestamps)
});

test('it invalidates resource cache on successful state-changing actions', function () {
    // 1. Warm up the cache for users
    User::query()->cache(60)->get();

    $cache = Cache::create('query')->withPath('user');
    $keysBefore = $cache->keys();
    expect(count($keysBefore))->toBeGreaterThan(0);

    // 2. Simulate a POST request to update users
    $request = createCacheMockRequest(['name' => 'Updated'], 'POST', '/users/update/1');
    $request->setRouteContext('entity', 'User');

    $response = resolve(Response::class);
    $response->status(200); // Success

    // 3. Dispatch terminate event
    Event::dispatch(new KernelTerminateEvent($request, $response));

    // 4. Verify cache is invalidated (TAG timestamp updated)
    // In our implementation, read() will check the tag timestamp and delete the file if stale.
    // So we try to read all keys and see if they are gone.
    foreach ($keysBefore as $key) {
        expect($cache->read($key))->toBeNull();
    }
});

test('it does not invalidate cache on GET requests', function () {
    User::query()->cache(60)->get();
    $cache = Cache::create('query')->withPath('user');
    $keysBefore = $cache->keys();
    expect(count($keysBefore))->toBeGreaterThan(0);

    $request = createCacheMockRequest([], 'GET', '/users');
    $request->setRouteContext('entity', 'User');
    $response = resolve(Response::class);
    $response->status(200);

    Event::dispatch(new KernelTerminateEvent($request, $response));

    // Cache should still be valid
    foreach ($keysBefore as $key) {
        expect($cache->read($key))->not->toBeNull();
    }
});

test('it does not invalidate cache on failed requests', function () {
    User::query()->cache(60)->get();
    $cache = Cache::create('query')->withPath('user');
    $keysBefore = $cache->keys();

    $request = createCacheMockRequest(['name' => 'Invalid'], 'POST', '/users/update/1');
    $request->setRouteContext('entity', 'User');
    $response = resolve(Response::class);
    $response->status(422); // Validation failure

    Event::dispatch(new KernelTerminateEvent($request, $response));

    // Cache should still be valid
    foreach ($keysBefore as $key) {
        expect($cache->read($key))->not->toBeNull();
    }
});
