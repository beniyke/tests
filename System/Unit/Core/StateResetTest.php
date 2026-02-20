<?php

declare(strict_types=1);

use Core\Event;
use Core\Ioc\Container;
use Core\Kernel;
use Core\ProviderManager;
use Helpers\File\Paths;
use Helpers\String\Inflector;

beforeEach(function () {
    Event::reset();
    Inflector::reset();
});

test('event listeners are preserved after reset', function () {
    $called = 0;
    $event = new stdClass();
    $eventClass = get_class($event);

    Event::listen($eventClass, function () use (&$called) {
        $called++;
    });

    Event::dispatch($event);
    expect($called)->toBe(1);

    Event::reset();

    Event::dispatch($event);
    expect($called)->toBe(2, 'Event listeners should be preserved after reset()');
});

test('inflector cache is cleared after reset', function () {
    // Access protected cache via reflection for verification
    $reflection = new ReflectionClass(Inflector::class);
    $cacheProperty = $reflection->getProperty('_cache');
    $cacheProperty->setAccessible(true);

    Inflector::pluralize('apple');
    expect($cacheProperty->getValue(null))->not->toBeEmpty('Inflector cache should be populated');

    Inflector::reset();
    expect($cacheProperty->getValue(null))->toBeEmpty('Inflector cache should be empty after reset');
});

test('kernel terminate calls resets', function () {
    // This is a bit of a mock test since we can't easily verify static calls
    // but we can ensure Kernel::terminate doesn't crash and we can verify
    // if Inflector is actually reset within it if we populate it first.

    Inflector::pluralize('car');
    $reflection = new ReflectionClass(Inflector::class);
    $cacheProperty = $reflection->getProperty('_cache');
    $cacheProperty->setAccessible(true);
    expect($cacheProperty->getValue(null))->not->toBeEmpty();

    $container = Container::getInstance();
    $container->instance(ProviderManager::class, new ProviderManager($container));
    $kernel = new Kernel($container, Paths::basePath());

    $kernel->terminate();

    expect($cacheProperty->getValue(null))->toBeEmpty('Kernel::terminate should have called Inflector::reset()');
});
