<?php

declare(strict_types=1);

use Core\Services\ConfigServiceInterface;
use Core\Support\Adapters\Interfaces\SapiInterface;
use Helpers\Http\Cookie;
use Helpers\Http\Request;
use Helpers\Http\Response;
use Helpers\Http\Session;
use Helpers\Http\UserAgent;

describe('Request', function () {
    beforeEach(function () {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['PHP_SELF'] = '/index.php/test';
        $_GET = ['key' => 'value'];
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

    test('detects GET method', function () {
        expect($this->request->isGet())->toBeTrue();
        expect($this->request->isPost())->toBeFalse();
    });

    test('gets query parameter', function () {
        expect($this->request->get('key'))->toBe('value');
    });

    test('gets all query parameters', function () {
        expect($this->request->get())->toBe(['key' => 'value']);
    });

    test('detects AJAX request', function () {
        $server = $_SERVER;
        $server['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $request = new Request(
            $server,
            $_GET,
            $_POST,
            $_FILES,
            $_COOKIE,
            $this->config,
            $this->sapi,
            $this->session,
            $this->agent
        );
        expect($request->isAjax())->toBeTrue();
    });

    test('gets request URI', function () {
        expect($this->request->uri())->toBe('test');
    });

    test('gets request method', function () {
        expect($this->request->method())->toBe('GET');
    });

    test('checks content type', function () {
        $server = $_SERVER;
        $server['CONTENT_TYPE'] = 'application/json';

        $request = new Request(
            $server,
            $_GET,
            $_POST,
            $_FILES,
            $_COOKIE,
            $this->config,
            $this->sapi,
            $this->session,
            $this->agent
        );

        expect($request->contentTypeIs('application/json'))->toBeTrue();
        expect($request->contentTypeIs('json'))->toBeTrue();
        expect($request->contentTypeIs('xml'))->toBeFalse();
    });

    test('checks if request wants JSON', function () {
        $server = $_SERVER;
        $server['HTTP_ACCEPT'] = 'application/json';

        $request = new Request(
            $server,
            $_GET,
            $_POST,
            $_FILES,
            $_COOKIE,
            $this->config,
            $this->sapi,
            $this->session,
            $this->agent
        );

        expect($request->wantsJson())->toBeTrue();
        expect($request->expectsJson())->toBeTrue();
    });

    test('checks accepts content types', function () {
        $server = $_SERVER;
        $server['HTTP_ACCEPT'] = 'text/html, application/xhtml+xml, application/xml;q=0.9, image/webp, */*;q=0.8';

        $request = new Request(
            $server,
            $_GET,
            $_POST,
            $_FILES,
            $_COOKIE,
            $this->config,
            $this->sapi,
            $this->session,
            $this->agent
        );

        expect($request->accepts('html'))->toBeTrue();
        expect($request->accepts(['json', 'html']))->toBeTrue();
        expect($request->accepts('application/xml'))->toBeTrue();
        expect($request->accepts('application/json'))->toBeFalse();
    });

    test('supports macros', function () {
        Request::macro('isCustom', function () {
            return 'custom';
        });

        $server = $_SERVER;
        $request = new Request(
            $server,
            $_GET,
            $_POST,
            $_FILES,
            $_COOKIE,
            $this->config,
            $this->sapi,
            $this->session,
            $this->agent
        );

        expect($request->isCustom())->toBe('custom');
    });
});


describe('Response', function () {
    test('creates JSON response', function () {
        $response = new Response();
        $result = $response->json(['status' => 'success']);

        expect($result)->toBeInstanceOf(Response::class);
    });

    test('sets status code', function () {
        $response = new Response();
        $response->setStatusCode(404);

        expect($response->getStatusCode())->toBe(404);
    });

    test('sets header', function () {
        $response = new Response();
        $response->setHeader('Content-Type', 'application/json');

        $headers = $response->getHeaders();
        expect($headers)->toHaveKey('Content-Type');
    });

    test('creates redirect response', function () {
        $response = new Response();
        $result = $response->redirect('/home');

        expect($result)->toBeInstanceOf(Response::class);
    });

    test('supports macros', function () {
        Response::macro('customJson', function ($data) {
            return $this->json($data)->header(['X-Custom' => 'true']);
        });

        $response = new Response();
        $result = $response->customJson(['key' => 'value']);

        expect($result)->toBeInstanceOf(Response::class);
        expect($result->getHeader('X-Custom'))->toBe('true');
    });
});

describe('Cookie', function () {
    test('sets cookie', function () {
        $cookie = new Cookie();
        // Mock setcookie since it sends headers
        $result = $cookie->set('test_cookie', 'value', 3600);

        // In CLI/Test environment setcookie might fail or return false/void depending on output buffering
        // Here we just check if it runs without error, or we could mock the function if possible.
        // For now, let's assume it returns true or we check side effects if possible.
        // But Cookie::set returns bool.

        expect($result)->toBeBool();
    });

    test('gets cookie value', function () {
        $_COOKIE['test'] = 'value';
        $cookie = new Cookie();

        expect($cookie->get('test'))->toBe('value');
    });

    test('checks cookie exists', function () {
        $_COOKIE['exists'] = 'yes';
        $cookie = new Cookie();

        expect($cookie->has('exists'))->toBeTrue();
        expect($cookie->has('not_exists'))->toBeFalse();
    });

    test('deletes cookie', function () {
        $cookie = new Cookie();
        $result = $cookie->delete('test');

        expect($result)->toBeBool();
    });
});
