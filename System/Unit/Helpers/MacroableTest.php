<?php

declare(strict_types=1);

use Helpers\Macroable;

class TestMacroableClass
{
    use Macroable;
}

describe('Macroable Trait', function () {
    afterEach(function () {
        TestMacroableClass::flushMacros();
    });

    test('can register and call a macro', function () {
        TestMacroableClass::macro('greet', function ($name) {
            return "Hello, {$name}!";
        });

        $instance = new TestMacroableClass();
        expect($instance->greet('World'))->toBe('Hello, World!');
    });

    test('can check if macro exists', function () {
        expect(TestMacroableClass::hasMacro('testMacro'))->toBeFalse();

        TestMacroableClass::macro('testMacro', function () {
            return 'exists';
        });

        expect(TestMacroableClass::hasMacro('testMacro'))->toBeTrue();
    });

    test('can flush macros', function () {
        TestMacroableClass::macro('tempMacro', function () {
            return 'temp';
        });

        expect(TestMacroableClass::hasMacro('tempMacro'))->toBeTrue();

        TestMacroableClass::flushMacros();

        expect(TestMacroableClass::hasMacro('tempMacro'))->toBeFalse();
    });

    test('macro can access object context', function () {
        TestMacroableClass::macro('getContext', function () {
            return $this;
        });

        $instance = new TestMacroableClass();
        expect($instance->getContext())->toBe($instance);
    });
});
