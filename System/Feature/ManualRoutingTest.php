<?php

declare(strict_types=1);

namespace Tests\System\Feature;

use Core\Ioc\Container;
use Core\Route\Route;
use Core\Services\ConfigServiceInterface;
use Tests\Fixtures\Middleware\DummyMiddleware;
use Tests\Fixtures\Middleware\SecondDummyMiddleware;

beforeEach(function () {
    Route::reset();

    // Disable CSRF for testing state-changing methods
    resolve(ConfigServiceInterface::class)->set('csrf.enable', false);

    // Register dummy middlewares
    Container::getInstance()->bind('auth', DummyMiddleware::class);
    Container::getInstance()->bind('second', SecondDummyMiddleware::class);
});

test('manual routing handles nested grouping and middleware chaining', function () {
    Route::group(['prefix' => 'admin', 'middleware' => 'auth'], function () {
        Route::group(['prefix' => 'settings', 'middleware' => 'second'], function () {
            Route::get('profile', function (): string {
                return 'Admin Settings Profile';
            });
        });

        Route::get('dashboard', function (): string {
            return 'Admin Dashboard';
        });
    });

    // Test nested prefix and chained middleware
    $this->get('/admin/settings/profile');
    $this->assertOk();
    $this->assertSee('Admin Settings Profile');

    // Verify both middlewares were executed via response headers
    $this->assertHeader('X-First-Middleware', 'passed');
    $this->assertHeader('X-Second-Middleware', 'passed');

    // Test single-level grouping in the same stack
    $this->get('/admin/dashboard');
    $this->assertOk();
    $this->assertSee('Admin Dashboard');
    $this->assertHeader('X-First-Middleware', 'passed');
});

test('manual routing supports dynamic parameters with regex constraints', function () {
    Route::get('user/{id}', function (string $id): string {
        return "User ID: $id";
    })->where('id', '[0-9]+');

    Route::get('post/{slug}', function (string $slug): string {
        return "Post Slug: $slug";
    })->where('slug', '[a-z-]+');

    // Valid numeric ID
    $this->get('/user/123');
    $this->assertOk();
    $this->assertSee('User ID: 123');

    // Invalid non-numeric ID (should 404 or skip to next)
    $this->get('/user/abc');
    $this->assertStatus(404);

    // Valid alpha slug
    $this->get('/post/hello-world');
    $this->assertOk();
    $this->assertSee('Post Slug: hello-world');

    // Invalid slug with numbers
    $this->get('/post/hello-123');
    $this->assertStatus(404);
});

test('manual routing handles named routes and fluent chaining', function () {
    $route = Route::get('named-route', function (): string {
        return 'named';
    })->name('test.name')->middleware('auth');

    $this->get('/named-route');
    $this->assertOk();
    $this->assertHeader('X-First-Middleware', 'passed');
});

test('manual routing supports all HTTP methods', function () {
    $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    foreach ($methods as $method) {
        $path = strtolower($method) . '-test';

        Route::{strtolower($method)}($path, function () use ($method): string {
            return "passed $method";
        });

        $this->call($method, '/' . $path);
        $this->assertOk();
        $this->assertSee("passed $method");
    }
});

test('manual routing resolves middleware groups from config', function () {
    // Register a test middleware group in config
    resolve(ConfigServiceInterface::class)->set('middleware.test_group', [DummyMiddleware::class]);

    Route::middleware('test_group')->get('group-protected', function () {
        return 'Group content';
    });

    $this->get('/group-protected');
    $this->assertOk();
    $this->assertSee('Group content');
    $this->assertHeader('X-First-Middleware', 'passed');
});

test('manual routing supports closure-based middleware', function () {
    Route::middleware(function ($request, $response, $next) {
        $response = $next($request, $response);

        return $response->setHeader('X-Closure-Middleware', 'Passed');
    })->get('closure-middleware', function () {
        return 'Closure middleware content';
    });

    $this->get('/closure-middleware');
    $this->assertOk();
    $this->assertHeader('X-Closure-Middleware', 'Passed');
    $this->assertSee('Closure middleware content');
});

test('manual routing enforces HTTP methods with 405 error', function () {
    Route::get('only-get', function (): string {
        return 'get';
    });

    $this->post('/only-get');
    $this->assertStatus(405);
    $this->assertSee('405');
    $this->assertSee('Method Not Allowed');
});

test('manual routing returns smart JSON 404 for API requests', function () {
    $this->json('GET', '/non-existent-api-route');

    $this->assertStatus(404);
    $this->assertHeader('Content-Type', 'application/json; charset=UTF-8');

    $this->assertJsonData([
        'status' => false,
        'message' => 'Resource not found',
        'error' => '404 Not Found'
    ]);
});

test('manual routing supports optional parameters', function () {
    Route::get('optional/{id?}', function (?string $id = 'default'): string {
        return "ID: $id";
    });

    // Provided
    $this->get('/optional/123');
    $this->assertOk();
    $this->assertSee('ID: 123');

    // Omitted
    $this->get('/optional');
    $this->assertOk();
    $this->assertSee('ID: default');
});

test('manual routing generates URLs for named routes', function () {
    Route::get('user/{id}/post/{slug}', function () {})->name('user.post');
    Route::get('optional-url/{id?}', function () {})->name('optional.name');

    // Basic
    $this->assertEquals('/user/1/post/hello', route_url('user.post', ['id' => 1, 'slug' => 'hello']));

    // Optional provided
    $this->assertEquals('/optional-url/123', route_url('optional.name', ['id' => 123]));

    // Optional omitted
    $this->assertEquals('/optional-url', route_url('optional.name'));
});

test('manual routing supports HTTP method spoofing', function () {
    Route::put('spoofed-put', function (): string {
        return 'spoofed put success';
    });

    Route::delete('spoofed-delete', function (): string {
        return 'spoofed delete success';
    });

    // Spoofed PUT via POST
    $this->post('/spoofed-put', ['_method' => 'PUT']);
    $this->assertOk();
    $this->assertSee('spoofed put success');

    // Spoofed DELETE via POST
    $this->post('/spoofed-delete', ['_method' => 'DELETE']);
    $this->assertOk();
    $this->assertSee('spoofed delete success');
});

test('manual routing supports root route', function () {
    Route::get('/', function (): string {
        return 'welcome to root';
    });

    $this->get('/');
    $this->assertOk();
    $this->assertSee('welcome to root');
});

test('manual routing inherits global middleware from config', function () {
    // Mock global auth config: 'account/{*}' requires 'auth' middleware
    $config = resolve(ConfigServiceInterface::class);
    $config->set('route.auth.web', ['account/{*}']);
    $config->set('middleware.web', [DummyMiddleware::class]);

    // Register manual route that matches 'account/{*}'
    Route::get('account/profile', function () {
        return 'Account Profile';
    });

    $this->get('/account/profile');
    $this->assertOk();
    $this->assertHeader('X-First-Middleware', 'passed');
});

test('manual routing supports surgical middleware exclusion via withoutMiddleware', function () {
    $config = resolve(ConfigServiceInterface::class);
    $config->set('route.auth.web', ['account/{*}']);
    $config->set('middleware.web', [DummyMiddleware::class]);

    // Register manual route and EXCLUDE the global middleware
    Route::get('account/public', function () {
        return 'Public Account Info';
    })->withoutMiddleware(DummyMiddleware::class);

    $this->get('/account/public');
    $this->assertOk();
    // Header should NOT be present
    $this->assertHeaderMissing('X-First-Middleware');
});

test('manual routing supports total middleware override via onlyMiddleware', function () {
    $config = resolve(ConfigServiceInterface::class);
    $config->set('route.auth.web', ['account/{*}']);
    $config->set('middleware.web', [DummyMiddleware::class]);

    // Register manual route with ONLY a specific middleware, bypassing 'web' (DummyMiddleware)
    Route::get('account/exclusive', function () {
        return 'Exclusive content';
    })->onlyMiddleware('second'); // Use 'second' which binds to SecondDummyMiddleware

    $this->get('/account/exclusive');
    $this->assertOk();
    $this->assertHeader('X-Second-Middleware', 'passed');
    // DummyMiddleware (from 'web') should NOT have run
    $this->assertHeaderMissing('X-First-Middleware');
});

test('manual routing supports hierarchical name prefixing via as', function () {
    Route::group(['as' => 'admin.', 'prefix' => 'admin'], function () {
        Route::group(['as' => 'settings.', 'prefix' => 'settings'], function () {
            Route::get('profile', function () {})->name('profile');
        });

        Route::get('dashboard', function () {})->name('dashboard');
    });

    // Verify nested name: admin.settings.profile
    $this->assertEquals('/admin/settings/profile', route_url('admin.settings.profile'));

    // Verify single level name: admin.dashboard
    $this->assertEquals('/admin/dashboard', route_url('admin.dashboard'));
});
