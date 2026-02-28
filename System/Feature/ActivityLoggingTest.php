<?php

declare(strict_types=1);

namespace Tests\System\Feature;

use Activity\Models\Activity;
use App\Enums\UserStatus;
use App\Models\User;
use App\Providers\EventServiceProvider;
use Core\Contracts\AuthServiceInterface;
use Core\Event;
use Core\Events\KernelTerminateEvent;
use Core\Ioc\Container;
use Helpers\Http\Response;
use Testing\Concerns\InteractsWithPackages;
use Testing\Concerns\RefreshDatabase;
use Testing\Fakes\RequestFake;

uses(RefreshDatabase::class, InteractsWithPackages::class);

beforeEach(function () {
    $this->refreshDatabase();
    $this->bootPackage('Activity', null, true);

    // Create a test user (required for foreign key and ActivityManager check)
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'gender' => 'male',
        'password' => 'password',
        'status' => UserStatus::Active,
        'refid' => 'ref_123'
    ]);

    // Register EventServiceProvider to ensure LogActivityListener is active
    (new EventServiceProvider(Container::getInstance()))->boot();

    // Mock AuthServiceInterface
    $auth = mock(AuthServiceInterface::class);
    $auth->shouldReceive('isAuthenticated')->andReturn(true)->byDefault();
    $auth->shouldReceive('user')->andReturn($this->user)->byDefault();
    Container::getInstance()->instance(AuthServiceInterface::class, $auth);
});


test('it logs state-changing actions on kernel terminate', function () {
    $request = RequestFake::create('/users/store', 'POST');

    $request->setRouteContext('domain', 'Account');
    $request->setRouteContext('entity', 'User');
    $request->setRouteContext('action', 'store');

    $response = resolve(Response::class);

    // Trigger terminal event manually for the test
    Event::dispatch(new KernelTerminateEvent($request, $response));

    // Verify log entry
    $log = Activity::first();

    expect($log)->not->toBeNull();
    expect($log->description)->toBe('STORE action on User in Account');
    expect($log->user_id)->toBe($this->user->id);
    expect($log->metadata['method'])->toBe('POST');
});

test('it does not log GET requests', function () {
    $request = RequestFake::create('/users', 'GET');
    $response = resolve(Response::class);

    Event::dispatch(new KernelTerminateEvent($request, $response));

    expect(Activity::count())->toBe(0);
});

test('it excludes sensitive data from meta', function () {
    $data = [
        'name' => 'John',
        'password' => 'secret123',
        'token' => 'abc'
    ];

    $request = RequestFake::create('/submit', 'POST', $data);
    $response = resolve(Response::class);

    Event::dispatch(new KernelTerminateEvent($request, $response));

    $log = Activity::first();
    expect($log)->not->toBeNull();

    $meta = $log->metadata;

    expect($meta['name'])->toBe('John');
    // Str::maskSensitiveData uses 10 stars for strings > 4 chars, and same length for strings <= 4
    expect($meta['password'])->toBe('**********');
    expect($meta['token'])->toBe('***');
});
