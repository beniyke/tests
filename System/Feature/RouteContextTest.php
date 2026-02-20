<?php

declare(strict_types=1);

namespace Tests\System\Feature;

use Core\App;
use Core\Ioc\Container;
use Core\Route\RouteMatch;
use Core\Views\ViewEngine;
use Helpers\Http\Request;
use ReflectionClass;

test('app hydrates route context automatically', function () {
    $container = Container::getInstance();
    $request = $container->get(Request::class);

    $match = new RouteMatch([
        'controller' => 'App\Account\Controllers\UserController',
        'method' => 'index',
        'parameters' => [],
        'middleware' => []
    ]);

    // Reflect to call hydrateRouteContext
    $app = $container->make(App::class);
    $reflection = new ReflectionClass($app);
    $method = $reflection->getMethod('hydrateRouteContext');
    $method->setAccessible(true);
    $method->invoke($app, $request, $match);

    expect($request->getRouteContext('domain'))->toBe('Account');
    expect($request->getRouteContext('entity'))->toBe('User');
    expect($request->getRouteContext('resource'))->toBe('users');
    expect($request->getRouteContext('action'))->toBe('index');
});

test('route context supports explicit overrides', function () {
    $container = Container::getInstance();
    $request = $container->get(Request::class);

    $match = new RouteMatch([
        'controller' => 'App\Account\Controllers\UserController',
        'method' => 'index',
        'parameters' => [],
        'middleware' => [],
        'context' => [
            'resource' => 'members',
            'custom' => 'value'
        ]
    ]);

    $app = $container->make(App::class);
    $reflection = new ReflectionClass($app);
    $method = $reflection->getMethod('hydrateRouteContext');
    $method->setAccessible(true);
    $method->invoke($app, $request, $match);

    expect($request->getRouteContext('resource'))->toBe('members');
    expect($request->getRouteContext('custom'))->toBe('value');
    // Dynamic ones still exist if not overridden
    expect($request->getRouteContext('entity'))->toBe('User');
});

test('view engine fluent helpers', function () {
    $container = Container::getInstance();
    $request = $container->get(Request::class);
    $request->setRouteContext('resource', 'users');
    $request->setRouteContext('action', 'edit');

    $view = $container->make(ViewEngine::class);

    $reflection = new ReflectionClass($view);

    $isResource = $reflection->getMethod('isResourceContext');
    $isResource->setAccessible(true);

    $isAction = $reflection->getMethod('isActionContext');
    $isAction->setAccessible(true);

    expect($isResource->invoke($view, 'users'))->toBeTrue();
    expect($isResource->invoke($view, 'roles'))->toBeFalse();

    expect($isAction->invoke($view, 'edit'))->toBeTrue();
    expect($isAction->invoke($view, 'create'))->toBeFalse();
});
