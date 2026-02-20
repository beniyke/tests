<?php

declare(strict_types=1);

use Core\Ioc\Container;
use Core\Services\ConfigServiceInterface;

test('config helper resolves host and env from default config', function () {
    /** @var ConfigServiceInterface $config */
    $config = Container::getInstance()->get(ConfigServiceInterface::class);

    // Ensure we are getting values possibly falling back to default.php or config cache
    $host = $config->get('host');
    $env = $config->get('env');

    // We expect these to match what config('...') helper would return
    expect(config('host'))->toBe($host);
    expect(config('env'))->toBe($env);

    // Verify they are not null (default.php should provide them)
    expect($host)->not->toBeNull();
    expect($env)->not->toBeNull();
});
