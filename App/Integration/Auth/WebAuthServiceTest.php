<?php

declare(strict_types=1);

use App\Models\Session as SessionModel;
use App\Models\User;
use App\Requests\LoginRequest;
use App\Services\ActivityLoggerService;
use App\Services\Auth\WebAuthService;
use App\Services\MenuService;
use App\Services\SessionService;
use App\Services\UserService;
use Core\Services\ConfigServiceInterface;
use Helpers\Data;
use Helpers\Http\Flash;
use Helpers\Http\Session;
use Helpers\Http\UserAgent;
use Notify\NotificationBuilder;
use Notify\Notifier;
use Security\Firewall\Drivers\AuthFirewall;

describe('WebAuthService Integration', function () {
    beforeEach(function () {

        $this->sessionService = Mockery::mock(SessionService::class);
        $this->userService = Mockery::mock(UserService::class);
        $this->flash = Mockery::mock(Flash::class);
        $this->firewall = Mockery::mock(AuthFirewall::class);
        $this->menuService = Mockery::mock(MenuService::class);
        $this->config = Mockery::mock(ConfigServiceInterface::class);
        $this->session = Mockery::mock(Session::class);
        $this->agent = Mockery::mock(UserAgent::class);

        // Mock ActivityLoggerService to prevent database failures in integration tests
        $this->activityLogger = Mockery::mock(ActivityLoggerService::class);
        $this->activityLogger->shouldReceive('description')->andReturnSelf();
        $this->activityLogger->shouldReceive('data')->andReturnSelf();
        $this->activityLogger->shouldReceive('user')->andReturnSelf();
        $this->activityLogger->shouldReceive('log')->andReturn(true);

        $this->notifier = Mockery::mock(Notifier::class);
        $this->notificationBuilder = Mockery::mock(NotificationBuilder::class);
        $this->notifier->shouldReceive('channel')->andReturn($this->notificationBuilder);
        $this->notificationBuilder->shouldReceive('with')->andReturnSelf();
        $this->notificationBuilder->shouldReceive('send')->andReturn(true);

        container()->bind(ActivityLoggerService::class, fn () => $this->activityLogger);
        container()->bind(Notifier::class, fn () => $this->notifier);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('isAuthenticated returns true when user has valid session', function () {
        $user = Mockery::mock(User::class)->makePartial();
        $sessionObj = Mockery::mock(SessionModel::class)->makePartial();
        $sessionObj->user = $user;
        $sessionObj->token = 'valid-token';

        $this->config->shouldReceive('get')->with('session.name')->andReturn('session_token');
        $this->session->shouldReceive('get')->with('session_token')->andReturn('valid-token');
        $this->sessionService->shouldReceive('getSessionByToken')->with('valid-token')->andReturn($sessionObj);
        $this->sessionService->shouldReceive('isSessionValid')->with($sessionObj)->andReturn(true);
        $this->sessionService->shouldReceive('refreshSession')->with($sessionObj)->once();

        $authService = new WebAuthService(
            $this->sessionService,
            $this->userService,
            $this->flash,
            $this->firewall,
            $this->menuService,
            $this->config,
            $this->session,
            $this->agent
        );

        expect($authService->isAuthenticated())->toBeTrue();
    });

    test('isAuthenticated returns false when session is missing', function () {
        $this->config->shouldReceive('get')->with('session.name')->andReturn('session_token');
        $this->session->shouldReceive('get')->with('session_token')->andReturn(null);

        $authService = new WebAuthService(
            $this->sessionService,
            $this->userService,
            $this->flash,
            $this->firewall,
            $this->menuService,
            $this->config,
            $this->session,
            $this->agent
        );

        expect($authService->isAuthenticated())->toBeFalse();
    });

    test('isAuthenticated returns false when session is invalid', function () {
        $sessionObj = Mockery::mock(SessionModel::class)->makePartial();
        $sessionObj->user = null;
        $sessionObj->token = 'invalid-token';

        $this->config->shouldReceive('get')->with('session.name')->andReturn('session_token');
        $this->session->shouldReceive('get')->with('session_token')->andReturn('invalid-token');
        $this->sessionService->shouldReceive('getSessionByToken')->with('invalid-token')->andReturn($sessionObj);
        $this->sessionService->shouldReceive('isSessionValid')->with($sessionObj)->andReturn(false);

        $authService = new WebAuthService(
            $this->sessionService,
            $this->userService,
            $this->flash,
            $this->firewall,
            $this->menuService,
            $this->config,
            $this->session,
            $this->agent
        );

        expect($authService->isAuthenticated())->toBeFalse();
    });

    test('user returns authenticated user from valid session', function () {
        $user = Mockery::mock(User::class)->makePartial();
        $sessionObj = Mockery::mock(SessionModel::class)->makePartial();
        $sessionObj->user = $user;
        $sessionObj->token = 'valid-token';

        $this->config->shouldReceive('get')->with('session.name')->andReturn('session_token');
        $this->session->shouldReceive('get')->with('session_token')->andReturn('valid-token');
        $this->sessionService->shouldReceive('getSessionByToken')->with('valid-token')->andReturn($sessionObj);
        $this->sessionService->shouldReceive('isSessionValid')->with($sessionObj)->andReturn(true);
        $this->sessionService->shouldReceive('refreshSession')->with($sessionObj)->once();

        $authService = new WebAuthService(
            $this->sessionService,
            $this->userService,
            $this->flash,
            $this->firewall,
            $this->menuService,
            $this->config,
            $this->session,
            $this->agent
        );

        expect($authService->user())->toBe($user);
    });

    test('user returns null when session is missing', function () {
        $this->config->shouldReceive('get')->with('session.name')->andReturn('session_token');
        $this->session->shouldReceive('get')->with('session_token')->andReturn(null);

        $authService = new WebAuthService(
            $this->sessionService,
            $this->userService,
            $this->flash,
            $this->firewall,
            $this->menuService,
            $this->config,
            $this->session,
            $this->agent
        );

        expect($authService->user())->toBeNull();
    });

    test('login succeeds with valid credentials', function () {
        $user = Mockery::mock(User::class)->makePartial();
        $user->name = 'John Doe';
        $user->id = 1;
        $user->shouldReceive('only')->with(['id', 'name', 'email'])->andReturn(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);

        $sessionObj = Mockery::mock(SessionModel::class)->makePartial();
        $sessionObj->token = 'new-session-token';
        $sessionObj->user = $user;
        $request = Mockery::mock(LoginRequest::class);

        $request->shouldReceive('isValid')->andReturn(true);
        $request->shouldReceive('getData')->andReturn(Data::make(['email' => 'john@example.com', 'password' => 'password']));
        $request->shouldReceive('hasRememberMe')->andReturn(false);

        $this->userService->shouldReceive('confirmUser')->andReturn($user);
        $this->config->shouldReceive('get')->with('session.cookie.remember_me_lifetime', 0)->andReturn(2592000);
        $this->config->shouldReceive('get')->with('session.timeout')->andReturn(3600);
        $this->config->shouldReceive('get')->with('session.name')->andReturn('session_token');
        $this->sessionService->shouldReceive('createNewSession')->with($user, 3600)->andReturn($sessionObj);
        $this->session->shouldReceive('delete')->with('session.long_lived')->once();
        $this->session->shouldReceive('regenerateId')->once();
        $this->session->shouldReceive('set')->with('session_token', 'new-session-token')->once();
        $this->firewall->shouldReceive('clear')->andReturnSelf();
        $this->firewall->shouldReceive('capture')->once();
        $this->flash->shouldReceive('success')->with('Welcome John Doe')->once();
        $this->agent->shouldReceive('browser')->andReturn('Chrome');

        $authService = new WebAuthService(
            $this->sessionService,
            $this->userService,
            $this->flash,
            $this->firewall,
            $this->menuService,
            $this->config,
            $this->session,
            $this->agent
        );

        expect($authService->login($request))->toBeTrue();
    });

    test('login fails with invalid credentials', function () {
        $request = Mockery::mock(LoginRequest::class);

        $request->shouldReceive('isValid')->andReturn(true);
        $request->shouldReceive('getData')->andReturn(Data::make(['email' => 'wrong@example.com', 'password' => 'wrong']));

        $this->userService->shouldReceive('confirmUser')->andReturn(null);
        $this->flash->shouldReceive('error')->with('Invalid login credentials.')->once();
        $this->firewall->shouldReceive('fail')->andReturnSelf();
        $this->firewall->shouldReceive('capture')->once();

        $authService = new WebAuthService(
            $this->sessionService,
            $this->userService,
            $this->flash,
            $this->firewall,
            $this->menuService,
            $this->config,
            $this->session,
            $this->agent
        );

        expect($authService->login($request))->toBeFalse();
    });

    test('login fails with invalid request', function () {
        $request = Mockery::mock(LoginRequest::class);

        $request->shouldReceive('isValid')->andReturn(false);

        $this->firewall->shouldReceive('fail')->andReturnSelf();
        $this->firewall->shouldReceive('capture')->once();

        $authService = new WebAuthService(
            $this->sessionService,
            $this->userService,
            $this->flash,
            $this->firewall,
            $this->menuService,
            $this->config,
            $this->session,
            $this->agent
        );

        expect($authService->login($request))->toBeFalse();
    });

    test('logout terminates session', function () {
        $this->config->shouldReceive('get')->with('session.name')->andReturn('session_token');
        $this->session->shouldReceive('get')->with('session_token')->andReturn('valid-token');
        $this->session->shouldReceive('delete')->with('session_token')->once();
        $this->session->shouldReceive('destroy')->once();
        $this->sessionService->shouldReceive('terminateSession')->with('valid-token')->andReturn(true);

        $authService = new WebAuthService(
            $this->sessionService,
            $this->userService,
            $this->flash,
            $this->firewall,
            $this->menuService,
            $this->config,
            $this->session,
            $this->agent
        );

        expect($authService->logout())->toBeTrue();
    });

    test('isAuthorized returns true when user can access route', function () {
        $user = Mockery::mock(User::class)->makePartial();
        $sessionObj = Mockery::mock(SessionModel::class)->makePartial();
        $sessionObj->user = $user;
        $sessionObj->token = 'valid-token';

        $this->config->shouldReceive('get')->with('session.name')->andReturn('session_token');
        $this->session->shouldReceive('get')->with('session_token')->andReturn('valid-token');
        $this->sessionService->shouldReceive('getSessionByToken')->with('valid-token')->andReturn($sessionObj);
        $this->sessionService->shouldReceive('isSessionValid')->with($sessionObj)->andReturn(true);
        $this->sessionService->shouldReceive('refreshSession')->with($sessionObj)->once();
        $user->shouldReceive('canLogin')->andReturn(true);
        $this->menuService->shouldReceive('getAccessibleRoutes')->with($user)->andReturn(['dashboard', 'profile']);

        $authService = new WebAuthService(
            $this->sessionService,
            $this->userService,
            $this->flash,
            $this->firewall,
            $this->menuService,
            $this->config,
            $this->session,
            $this->agent
        );

        expect($authService->isAuthorized('dashboard'))->toBeTrue();
    });

    test('isAuthorized returns false when user cannot access route', function () {
        $user = Mockery::mock(User::class)->makePartial();
        $sessionObj = Mockery::mock(SessionModel::class)->makePartial();
        $sessionObj->user = $user;
        $sessionObj->token = 'valid-token';

        $this->config->shouldReceive('get')->with('session.name')->andReturn('session_token');
        $this->session->shouldReceive('get')->with('session_token')->andReturn('valid-token');
        $this->sessionService->shouldReceive('getSessionByToken')->with('valid-token')->andReturn($sessionObj);
        $this->sessionService->shouldReceive('isSessionValid')->with($sessionObj)->andReturn(true);
        $this->sessionService->shouldReceive('refreshSession')->with($sessionObj)->once();
        $user->shouldReceive('canLogin')->andReturn(true);
        $this->menuService->shouldReceive('getAccessibleRoutes')->with($user)->andReturn(['dashboard']);

        $authService = new WebAuthService(
            $this->sessionService,
            $this->userService,
            $this->flash,
            $this->firewall,
            $this->menuService,
            $this->config,
            $this->session,
            $this->agent
        );

        expect($authService->isAuthorized('admin'))->toBeFalse();
    });
});
