<?php

declare(strict_types=1);

use Core\Services\ConfigServiceInterface;
use Core\Support\Adapters\Interfaces\SapiInterface;
use Helpers\Http\Request;
use Helpers\Http\Session;
use Helpers\Http\UserAgent;

describe('Request Authentication', function () {
    beforeEach(function () {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['PHP_SELF'] = '/index.php/test';
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_COOKIE = [];

        $this->config = Mockery::mock(ConfigServiceInterface::class);
        $this->sapi = Mockery::mock(SapiInterface::class);
        $this->session = Mockery::mock(Session::class);
        $this->agent = Mockery::mock(UserAgent::class);

        $this->config->shouldReceive('get')->andReturn(null);
        $this->sapi->shouldReceive('isPhpServer')->andReturn(false);
        $this->sapi->shouldReceive('isCli')->andReturn(false);
        $this->agent->shouldReceive('ip')->andReturn('127.0.0.1');
        $this->agent->shouldReceive('agentString')->andReturn('TestAgent');
        $this->agent->shouldReceive('isRobot')->andReturn(false);

        $this->request = new Request(
            $_SERVER,
            $_GET,
            $_POST,
            $_FILES,
            $_COOKIE,
            $this->config,
            $this->sapi,
            $this->session,
            $this->agent
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    test('sets and gets authenticated user', function () {
        $user = new stdClass();
        $user->id = 1;
        $user->name = 'Test User';

        $this->request->setAuthenticatedUser($user);

        expect($this->request->getAuthenticatedUser())->toBe($user);
        expect($this->request->user())->toBe($user);
    });

    test('sets and gets auth token', function () {
        $token = 'test-token-123';

        $this->request->setAuthToken($token);

        expect($this->request->token())->toBe($token);
    });

    test('returns null for unauthenticated user', function () {
        expect($this->request->getAuthenticatedUser())->toBeNull();
        expect($this->request->user())->toBeNull();
    });

    test('returns null for missing auth token', function () {
        expect($this->request->token())->toBeNull();
    });
});
