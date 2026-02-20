<?php

declare(strict_types=1);

namespace Tests\System\Feature;

use Core\Event;
use Core\Events\KernelTerminateEvent;
use Core\Ioc\Container;
use Defer\DeferrerInterface;
use Helpers\Http\Request;
use Helpers\Http\Response;

test('deferred tasks are executed on kernel termination via event', function () {
    $container = Container::getInstance();
    $request = $container->get(Request::class);
    $response = $container->get(Response::class);

    $deferrer = $container->get(DeferrerInterface::class);

    $executed = false;
    $deferrer->push(function () use (&$executed) {
        $executed = true;
    });

    // Ensure it's not executed yet
    expect($executed)->toBeFalse();

    // Dispatch the event manually to simulate kernel termination
    Event::dispatch(new KernelTerminateEvent($request, $response));

    // Verify it was executed by the listener
    expect($executed)->toBeTrue();
});
