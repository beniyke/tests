<?php

declare(strict_types=1);

namespace Tests\System\Feature;

use Core\Ioc\Container;
use Core\Views\ViewEngine;
use Helpers\Http\Request;
use Rank\Providers\RankServiceProvider;
use Rank\Rank;
use Testing\Concerns\InteractsWithPackages;
use Testing\Concerns\RefreshDatabase;

uses(RefreshDatabase::class, InteractsWithPackages::class);

beforeEach(function () {
    // Boot the provider to register the macro
    $provider = new RankServiceProvider(Container::getInstance());
    $provider->register();
    $provider->boot();
});

test('view engine has rank macro', function () {
    $view = resolve(ViewEngine::class);
    expect(ViewEngine::hasMacro('rank'))->toBeTrue();
    expect($view->rank())->toBeInstanceOf(Rank::class);
});

test('it generates automated title from route context', function () {
    $request = resolve(Request::class);
    $request->setRouteContext('entity', 'User');
    $request->setRouteContext('action', 'Edit');

    $view = resolve(ViewEngine::class);
    $html = $view->rank()->render();

    expect($html)->toContain('<title>Edit User</title>');
    expect($html)->toContain('<meta property="og:title" content="Edit User">');
});

test('it allows manual overrides', function () {
    $view = resolve(ViewEngine::class);
    $view->rank()->setTitle('Custom Title')->setDescription('Custom Description');

    $html = $view->rank()->render();

    expect($html)->toContain('<title>Custom Title</title>');
    expect($html)->toContain('<meta name="description" content="Custom Description">');
});

test('it renders OG and Twitter tags', function () {
    $view = resolve(ViewEngine::class);
    $view->rank()->setImage('https://example.com/image.jpg');

    $html = $view->rank()->render();

    expect($html)->toContain('<meta property="og:image" content="https://example.com/image.jpg">');
    expect($html)->toContain('<meta name="twitter:image" content="https://example.com/image.jpg">');
});
