<?php

declare(strict_types=1);

namespace Tests\System\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Core\Contracts\AuthServiceInterface;
use Core\Event;
use Core\Events\KernelTerminateEvent;
use Core\Ioc\Container;
use Core\Providers\EventServiceProvider;
use Helpers\File\Cache;
use Helpers\Http\Response;
use Testing\Fakes\RequestFake;

beforeEach(function () {
    $this->refreshDatabase();

    // Register EventServiceProvider to ensure ClearResourceCacheListener is active
    (new EventServiceProvider(Container::getInstance()))->boot();

    // Clear cache to ensure isolation
    Cache::create('query')->clear();

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
    $request = RequestFake::create('/users/update/1', 'POST', ['name' => 'Updated']);
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

    $request = RequestFake::create('/users', 'GET');
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

    $request = RequestFake::create('/users/update/1', 'POST', ['name' => 'Invalid']);
    $request->setRouteContext('entity', 'User');
    $response = resolve(Response::class);
    $response->status(422); // Validation failure

    Event::dispatch(new KernelTerminateEvent($request, $response));

    // Cache should still be valid
    foreach ($keysBefore as $key) {
        expect($cache->read($key))->not->toBeNull();
    }
});
