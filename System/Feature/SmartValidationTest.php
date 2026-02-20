<?php

declare(strict_types=1);

namespace Tests\System\Feature;

use App\Account\Requests\UserRequest;
use App\Account\Validations\Form\UserFormRequestValidation;
use App\Middleware\SmartValidationMiddleware;
use Database\Connection;
use Database\DB;
use Database\Schema\Schema;
use Helpers\Http\Request;
use Helpers\Http\Response;
use Helpers\Http\Session;
use Helpers\Http\UserAgent;

beforeEach(function () {
    $this->connection = Connection::configure('sqlite::memory:')
        ->name('test_smart_validation')
        ->connect();

    DB::setDefaultConnection($this->connection);
    Schema::setConnection($this->connection);

    // Create required tables for Validator's exist/unique rules
    Schema::create('user', function ($table) {
        $table->id();
        $table->string('email')->unique();
    });

    Schema::create('permit_role', function ($table) {
        $table->id();
        $table->string('slug')->unique();
    });

    DB::table('permit_role')->insert(['slug' => 'admin']);

    $this->middleware = resolve(SmartValidationMiddleware::class);
});

function createMockRequest(array $data = [], array $server = []): Request
{
    $_POST = $data;
    unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['HTTP_ACCEPT'], $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
    $_SERVER = array_merge($_SERVER, $server);

    return Request::createFromGlobals(
        resolve(\Core\Services\ConfigServiceInterface::class),
        resolve(\Core\Support\Adapters\Interfaces\SapiInterface::class),
        resolve(Session::class),
        resolve(UserAgent::class)
    );
}

test('middleware passes and injects DTO on valid data', function () {
    $response = resolve(Response::class);

    // Context that matches App\Account\Validations\Form\UserFormRequestValidation
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'admin',
        'status' => 'active',
        'gender' => 'male'
    ];

    $request = createMockRequest($data);
    $request->setRouteContext('domain', 'Account');
    $request->setRouteContext('entity', 'User');
    $request->setRouteContext('action', 'store');

    $this->middleware->handle($request, $response, function ($req, $res) {
        expect($req->validated())->toBeInstanceOf(UserRequest::class);
        expect($req->validated()->name)->toBe('John Doe');

        return $res;
    });
});

test('middleware redirects on invalid web data', function () {
    $response = resolve(Response::class);

    $data = ['name' => 'Jo'];
    $request = createMockRequest($data);
    $request->setRouteContext('domain', 'Account');
    $request->setRouteContext('entity', 'User');
    $request->setRouteContext('action', 'store');

    $result = $this->middleware->handle($request, $response, function ($req, $res) {
        return $res;
    });

    expect($result->getStatusCode())->toBe(302);
});

test('middleware returns 422 JSON on invalid API data', function () {
    $response = resolve(Response::class);

    $data = [
        'name' => 'Jo', // Too short
        'email' => 'invalid', // Invalid format
        'role' => 'invalid', // Doesn't exist
        'status' => 'invalid', // Doesn't exist
        'gender' => 'invalid', // Doesn't exist
    ];
    $request = createMockRequest($data, [
        'HTTP_ACCEPT' => 'application/json',
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/api/users'
    ]);

    $request->setRouteContext('domain', 'Account');
    $request->setRouteContext('entity', 'User');
    $request->setRouteContext('action', 'store');
    $request->setRouteContext('validator', UserFormRequestValidation::class);

    $result = $this->middleware->handle($request, $response, function ($req, $res) {
        return $res;
    });

    expect($result->getStatusCode())->toBe(422);
    $content = json_decode($result->getContent(), true);
    expect($content['message'])->toBe('The given data was invalid.');
    expect($content['errors'])->toHaveKey('name');
    expect($content['errors'])->toHaveKey('email');
});

test('middleware respects explicit validator in route context', function () {
    $response = resolve(Response::class);

    $data = ['name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'admin', 'status' => 'active', 'gender' => 'male'];
    $request = createMockRequest($data);

    // Set a validator explicitly
    $request->setRouteContext('validator', UserFormRequestValidation::class);

    $this->middleware->handle($request, $response, function ($req, $res) {
        expect($req->validated())->toBeInstanceOf(UserRequest::class);

        return $res;
    });
});

test('middleware returns JSON on AJAX request failure', function () {
    $response = resolve(Response::class);

    $data = ['name' => 'Jo'];
    $request = createMockRequest($data, [
        'HTTP_X_REQUESTED_WITH' => 'XMLHTTPRequest'
    ]);

    $request->setRouteContext('domain', 'Account');
    $request->setRouteContext('entity', 'User');
    $request->setRouteContext('action', 'store');

    $result = $this->middleware->handle($request, $response, function ($req, $res) {
        return $res;
    });

    expect($result->getStatusCode())->toBe(422);
    expect($result->getContent())->toContain('The given data was invalid.');
});

test('request can perform manual validation via validateUsing()', function () {
    $response = resolve(Response::class);

    $data = ['name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'admin', 'status' => 'active', 'gender' => 'male'];
    $request = createMockRequest($data);

    // Register the middleware (which registers the resolver)
    $this->middleware->handle($request, $response, function ($req, $res) {
        // Now perform manual validation
        $req->validateUsing(UserFormRequestValidation::class);

        expect($req->validated())->toBeInstanceOf(UserRequest::class);
        expect($req->validationAlreadyPerformed())->toBeTrue();

        return $res;
    });
});

test('manual validation through request handles failure via middleware', function () {
    $response = resolve(Response::class);

    $data = ['name' => 'Jo'];
    $request = createMockRequest($data);

    $result = $this->middleware->handle($request, $response, function ($req, $res) {
        // This should throw ValidationException which middleware catches
        $req->validateUsing(UserFormRequestValidation::class);

        return $res;
    });

    expect($result->getStatusCode())->toBe(302); // Redirect back on web failure
});
