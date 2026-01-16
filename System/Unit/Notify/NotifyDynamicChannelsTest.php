<?php

declare(strict_types=1);

use Notify\Notify;

describe('Notify Facade Dynamic Channels', function () {
    it('supports dynamic channel calls via __callStatic', function () {
        expect(method_exists(Notify::class, '__callStatic'))->toBeTrue();
    });

    it('__callStatic accepts channel name and arguments', function () {
        $reflection = new ReflectionMethod(Notify::class, '__callStatic');
        $parameters = $reflection->getParameters();

        expect($parameters)->toHaveCount(2);
        expect($parameters[0]->getName())->toBe('channelName');
        expect($parameters[1]->getName())->toBe('arguments');
    });

    it('throws exception when insufficient arguments provided', function () {
        expect(fn () => Notify::whatsapp('OnlyOneArg'))
            ->toThrow(BadMethodCallException::class);
    });
});
