<?php

declare(strict_types=1);

namespace Tests\System\Feature;

use Core\Route\Route;
use Core\Route\UrlResolver;
use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Tests\System\Support\Routing\DummyController;

beforeEach(function () {
    Route::reset();
    UrlResolver::reset();

    // Setup a common test view for routing tests
    $this->testView = Paths::templatePath('test-route-view.php');
    FileSystem::put($this->testView, 'Hello, <?= $name ?>!');
});

afterEach(function () {
    if (isset($this->testView) && FileSystem::isFile($this->testView)) {
        FileSystem::delete($this->testView);
    }
});

describe('Manual Routing Convenience Methods', function () {

    test('it supports any() for all common HTTP methods', function () {
        Route::exclusive()->group(function () {
            Route::any('test-any-route', function () {
                return 'any';
            });
        });

        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        foreach ($methods as $method) {
            $this->call($method, 'test-any-route');
            $this->assertOk();
            $this->assertSee('any');
        }
    });

    test('it supports match() for specific subset of methods', function () {
        Route::exclusive()->group(function () {
            Route::match(['GET', 'POST'], 'test-match-route', function () {
                return 'match';
            });
        });

        $this->get('test-match-route');
        $this->assertOk();
        $this->assertSee('match');

        $this->post('test-match-route');
        $this->assertOk();
        $this->assertSee('match');

        $this->call('PUT', 'test-match-route');
        $this->assertStatus(405);
    });

    test('it supports redirect() with custom status codes', function () {
        Route::exclusive()->redirect('test-old-path', '/test-new-path', 301);

        $this->get('test-old-path');
        $this->assertStatus(301);
        $this->assertHeader('Location', '/test-new-path');
    });

    test('it supports view() rendering with data passing', function () {
        // This will now use ViewInterface properly with a dedicated test view
        Route::exclusive()->view('test-view-route', 'test-route-view', ['name' => 'Anchor']);

        $this->get('/test-view-route');
        $this->assertOk();
        $this->assertSee('Hello, Anchor!');
    });

    test('it supports view() with absolute paths', function () {
        $tempView = Paths::storagePath('anchor_test_view.php');
        FileSystem::put($tempView, '<h1>Absolute View</h1><p>Data: <?= $data ?></p>');

        Route::exclusive()->view('abs-view', $tempView, ['data' => 'Verified']);

        $this->get('/abs-view');
        $this->assertOk();
        $this->assertSee('Absolute View');
        $this->assertSee('Data: Verified');

        FileSystem::delete($tempView);
    });

    test('it supports view() with module syntax', function () {
        // We use the Auth module which exists in the app source
        $moduleView = Paths::templatePath('dynamic-test-view.php', 'Auth');
        FileSystem::put($moduleView, '<h1>Auth Module View</h1><p>Status: <?= $status ?></p>');

        Route::exclusive()->view('module-view', 'Auth::dynamic-test-view', ['status' => 'Verified']);

        $this->get('/module-view');
        $this->assertOk();
        $this->assertSee('Auth Module View');
        $this->assertSee('Status: Verified');

        FileSystem::delete($moduleView);
    });

    test('it supports resource() for full CRUD route generation', function () {
        $controller = DummyController::class;

        Route::exclusive()->group(function () use ($controller) {
            Route::resource('test-photos', $controller);
        });

        // GET /test-photos -> index
        $this->get('/test-photos');
        $this->assertOk();
        $this->assertSee('index');

        // GET /test-photos/create -> create
        $this->get('/test-photos/create');
        $this->assertOk();
        $this->assertSee('create');

        // POST /test-photos -> store
        $this->post('/test-photos');
        $this->assertOk();
        $this->assertSee('store');

        // GET /test-photos/1 -> show
        $this->get('/test-photos/1');
        $this->assertOk();
        $this->assertSee('show 1');

        // GET /test-photos/1/edit -> edit
        $this->get('/test-photos/1/edit');
        $this->assertOk();
        $this->assertSee('edit 1');

        // PUT /test-photos/1 -> update
        $this->call('PUT', '/test-photos/1');
        $this->assertOk();
        $this->assertSee('update 1');

        // DELETE /test-photos/1 -> destroy
        $this->delete('/test-photos/1');
        $this->assertOk();
        $this->assertSee('confirm delete 1');

        // Verify named routes
        $this->assertEquals('/test-photos', route_url('test-photos.index'));
        $this->assertEquals('/test-photos/create', route_url('test-photos.create'));
        $this->assertEquals('/test-photos/1', route_url('test-photos.show', ['id' => 1]));
    });

    test('it supports fallback() catch-all routing', function () {
        // Add a specific route that works
        Route::exclusive()->get('test-specific', function () {
            return 'specific';
        });

        Route::fallback(function () {
            return 'fallback';
        });

        // Match specific
        $this->get('/test-specific');
        $this->assertOk();
        $this->assertSee('specific');

        // Match fallback
        $this->get('/test-something-else');
        $this->assertOk();
        $this->assertSee('fallback');
    });

    test('it supports fallbackView() for direct view rendering', function () {
        Route::fallbackView('test-route-view', ['name' => 'Fallback Anchor']);

        $this->get('/some-non-existent-page');
        $this->assertOk();
        $this->assertSee('Hello, Fallback Anchor!');
    });

    test('convenience methods respect group prefixing and naming', function () {
        Route::exclusive()->prefix('test-blog')->as('test-blog.')->group(function () {
            Route::resource('posts', DummyController::class);
            Route::redirect('legacy', '/test-blog/posts');
            Route::any('contact', function () {
                return 'contact';
            });
        });

        // Check resource names
        $this->assertEquals('/test-blog/posts', route_url('test-blog.posts.index'));

        // Check redirect
        $this->get('test-blog/legacy');
        $this->assertStatus(302);
        $this->assertHeader('Location', '/test-blog/posts');

        // Check any
        $this->post('test-blog/contact');
        $this->assertOk();
        $this->assertSee('contact');
    });
});
