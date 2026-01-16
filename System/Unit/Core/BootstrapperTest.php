<?php

declare(strict_types=1);

use Core\Bootstrapper;
use Core\Ioc\ContainerInterface;
use Helpers\File\Paths;

describe('Bootstrapper', function () {
    $originalPath = '';

    beforeEach(function () use (&$originalPath) {
        $originalPath = rtrim(Paths::basePath(), DIRECTORY_SEPARATOR);
    });

    afterEach(function () use (&$originalPath) {
        Paths::setBasePath($originalPath);
    });

    test('constructor sets base path', function () {
        $container = Mockery::mock(ContainerInterface::class);
        $bootstrapper = new Bootstrapper($container, '/app/path');

        expect($bootstrapper)->toBeInstanceOf(Bootstrapper::class);
        expect(rtrim(Paths::basePath(), DIRECTORY_SEPARATOR))->toBe('/app/path');
    });

    test('constructor accepts container and path', function () {
        $container = Mockery::mock(ContainerInterface::class);

        expect(fn () => new Bootstrapper($container, '/test/path'))
            ->not->toThrow(Exception::class);

        expect(rtrim(Paths::basePath(), DIRECTORY_SEPARATOR))->toBe('/test/path');
    });
});
