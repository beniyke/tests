<?php

declare(strict_types=1);

use Tests\System\Support\Helpers\Mixins\SampleMixin;
use Tests\System\Support\Helpers\Mixins\TestMixableClass;

describe('Mixins Trait', function () {
    afterEach(function () {
        TestMixableClass::flushMacros();
    });

    test('can mixin methods from a class', function () {
        TestMixableClass::mixin(new SampleMixin());

        $instance = new TestMixableClass();
        expect($instance->greet('Mixer'))->toBe('Hello, Mixer!');
        expect($instance->secret())->toBe('Shhh...');
    });

    test('can mixin methods from a class string', function () {
        TestMixableClass::mixin(SampleMixin::class);

        $instance = new TestMixableClass();
        expect($instance->greet('String'))->toBe('Hello, String!');
    });

    test('respects the replace parameter', function () {
        TestMixableClass::macro('greet', function () {
            return "Original";
        });

        TestMixableClass::mixin(new SampleMixin(), false);

        $instance = new TestMixableClass();
        expect($instance->greet())->toBe('Original');

        TestMixableClass::mixin(new SampleMixin(), true);
        expect($instance->greet('New'))->toBe('Hello, New!');
    });
});
