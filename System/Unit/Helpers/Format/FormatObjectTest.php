<?php

declare(strict_types=1);

use Helpers\Format\FormatObject;

describe('FormatObject', function () {
    test('converts object to array', function () {
        $obj = new stdClass();
        $obj->name = 'test';
        $obj->value = 123;

        $array = FormatObject::make($obj)->asArray()->get();

        expect($array)->toBeArray();
        expect($array)->toBe(['name' => 'test', 'value' => 123]);
    });

    test('handles nested objects', function () {
        $obj = new stdClass();
        $obj->child = new stdClass();
        $obj->child->name = 'nested';

        $array = FormatObject::make($obj)->asArray()->get();

        expect($array['child'])->toBeInstanceOf(stdClass::class);
    });
});
