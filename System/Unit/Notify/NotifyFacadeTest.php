<?php

declare(strict_types=1);

use Notify\Notify;

describe('Notify Facade', function () {
    it('has static channel method', function () {
        expect(method_exists(Notify::class, 'channel'))->toBeTrue();
    });

    it('has static email shortcut method', function () {
        expect(method_exists(Notify::class, 'email'))->toBeTrue();
    });

    it('has static inapp shortcut method', function () {
        expect(method_exists(Notify::class, 'inapp'))->toBeTrue();
    });


    it('has static send method', function () {
        expect(method_exists(Notify::class, 'send'))->toBeTrue();
    });

    it('channel method returns NotificationBuilder', function () {
        $reflection = new ReflectionMethod(Notify::class, 'channel');
        $returnType = $reflection->getReturnType();

        expect($returnType->getName())->toBe('Notify\NotificationBuilder');
    });

    it('email method accepts correct parameters', function () {
        $reflection = new ReflectionMethod(Notify::class, 'email');
        $parameters = $reflection->getParameters();

        expect($parameters)->toHaveCount(2);
        expect($parameters[0]->getName())->toBe('notificationClass');
        expect($parameters[1]->getName())->toBe('payload');
    });
});
