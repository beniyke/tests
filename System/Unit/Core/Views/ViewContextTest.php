<?php

declare(strict_types=1);

use Core\Services\ConfigServiceInterface;
use Core\Support\Adapters\Interfaces\EnvironmentInterface;
use Core\Views\ViewEngine;
use Helpers\Http\Flash;
use Helpers\Http\Request;
use Helpers\Http\Response;
use Helpers\Http\Session;

beforeEach(function () {
    $this->request = Mockery::mock(Request::class);
    $this->response = Mockery::mock(Response::class);
    $this->config = Mockery::mock(ConfigServiceInterface::class);
    $this->flash = Mockery::mock(Flash::class);
    $this->environment = Mockery::mock(EnvironmentInterface::class);
    $this->session = Mockery::mock(Session::class);

    $this->viewEngine = new ViewEngine(
        $this->request,
        $this->response,
        $this->config,
        $this->flash,
        $this->environment,
        $this->session
    );
});

afterEach(function () {
    Mockery::close();
});

function invokePrivateMethod($object, $methodName, array $parameters = [])
{
    $reflection = new ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
}

it('returns true when user has permission via canAccessAction (string)', function () {
    $this->request->shouldReceive('getRouteContext')->with('resource')->andReturn('users');

    $user = Mockery::mock('stdClass');
    $user->shouldReceive('hasPermission')->with('users.edit')->andReturn(true);

    $this->request->shouldReceive('user')->andReturn($user);

    $result = invokePrivateMethod($this->viewEngine, 'canAccessAction', ['edit']);
    expect($result)->toBeTrue();
});

it('returns true when user has at least one permission via canAccessAction (array)', function () {
    $this->request->shouldReceive('getRouteContext')->with('resource')->andReturn('users');

    $user = Mockery::mock('stdClass');
    $user->shouldReceive('hasPermission')->with('users.create')->andReturn(false);
    $user->shouldReceive('hasPermission')->with('users.delete')->andReturn(true);

    $this->request->shouldReceive('user')->andReturn($user);

    $result = invokePrivateMethod($this->viewEngine, 'canAccessAction', [['create', 'delete']]);
    expect($result)->toBeTrue();
});

it('returns true when user has at least one permission via canAccessAction (pipe string)', function () {
    $this->request->shouldReceive('getRouteContext')->with('resource')->andReturn('users');

    $user = Mockery::mock('stdClass');
    $user->shouldReceive('hasPermission')->with('users.create')->andReturn(false);
    $user->shouldReceive('hasPermission')->with('users.delete')->andReturn(true);

    $this->request->shouldReceive('user')->andReturn($user);

    $result = invokePrivateMethod($this->viewEngine, 'canAccessAction', ['create|delete']);
    expect($result)->toBeTrue();
});

it('returns false when user has none of the permissions via canAccessAction (pipe string)', function () {
    $this->request->shouldReceive('getRouteContext')->with('resource')->andReturn('users');

    $user = Mockery::mock('stdClass');
    $user->shouldReceive('hasPermission')->with('users.create')->andReturn(false);
    $user->shouldReceive('hasPermission')->with('users.delete')->andReturn(false);

    $this->request->shouldReceive('user')->andReturn($user);

    $result = invokePrivateMethod($this->viewEngine, 'canAccessAction', ['create|delete']);
    expect($result)->toBeFalse();
});

it('returns false when user has none of the permissions via canAccessAction (array)', function () {
    $this->request->shouldReceive('getRouteContext')->with('resource')->andReturn('users');

    $user = Mockery::mock('stdClass');
    $user->shouldReceive('hasPermission')->with('users.create')->andReturn(false);
    $user->shouldReceive('hasPermission')->with('users.delete')->andReturn(false);

    $this->request->shouldReceive('user')->andReturn($user);

    $result = invokePrivateMethod($this->viewEngine, 'canAccessAction', [['create', 'delete']]);
    expect($result)->toBeFalse();
});

it('identifies active resource via isResourceActive', function () {
    $this->request->shouldReceive('getRouteContext')->with('resource')->andReturn('users');

    $result = invokePrivateMethod($this->viewEngine, 'isResourceActive', ['users']);
    expect($result)->toBe('active');

    $result = invokePrivateMethod($this->viewEngine, 'isResourceActive', ['posts']);
    expect($result)->toBe('');
});

it('retrieves route title from config via getRouteTitle', function () {
    $this->request->shouldReceive('getRoutePermission')->andReturn('users.edit');
    $this->config->shouldReceive('get')->with('permit.titles.users.edit', 'Default')->andReturn('Edit User');

    $result = invokePrivateMethod($this->viewEngine, 'getRouteTitle', ['Default']);
    expect($result)->toBe('Edit User');
});

it('generates breadcrumbs from route context', function () {
    $this->request->shouldReceive('getRouteContext')->with('resource')->andReturn('users');
    $this->request->shouldReceive('getRouteContext')->with('action')->andReturn('edit');

    $result = invokePrivateMethod($this->viewEngine, 'getBreadcrumbs');

    expect($result)->toBe([
        ['label' => 'Dashboard', 'url' => 'home'],
        ['label' => 'Users', 'url' => 'users.index'],
        ['label' => 'Edit', 'url' => null],
    ]);
});
