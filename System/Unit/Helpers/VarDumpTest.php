<?php

declare(strict_types=1);

use Helpers\VarDump;

describe('VarDump', function () {
    test('dump outputs formatted variable content', function () {
        $dumper = new VarDump();

        ob_start();
        $dumper->dump('test string');
        $output = ob_get_clean();

        expect($output)->toContain('test string');
        expect($output)->toContain('string');
        expect($output)->toContain('length:11');
    });

    test('dump handles arrays', function () {
        $dumper = new VarDump();
        $data = ['key' => 'value'];

        ob_start();
        $dumper->dump($data);
        $output = ob_get_clean();

        expect($output)->toContain('Array');
        expect($output)->toContain('key');
        expect($output)->toContain('value');
    });

    test('dump handles objects', function () {
        $dumper = new VarDump();
        $obj = new stdClass();
        $obj->prop = 'value';

        ob_start();
        $dumper->dump($obj);
        $output = ob_get_clean();

        expect($output)->toContain('Object');
        expect($output)->toContain('stdClass');
        expect($output)->toContain('prop');
        expect($output)->toContain('value');
    });

    test('dump handles multiple arguments', function () {
        $dumper = new VarDump();

        ob_start();
        $dumper->dump('one', 'two');
        $output = ob_get_clean();

        expect($output)->toContain('one');
        expect($output)->toContain('two');
    });

    test('dump handles null', function () {
        $dumper = new VarDump();

        ob_start();
        $dumper->dump(null);
        $output = ob_get_clean();

        expect($output)->toContain('null');
    });

    test('dump handles booleans', function () {
        $dumper = new VarDump();

        ob_start();
        $dumper->dump(true, false);
        $output = ob_get_clean();

        expect($output)->toContain('true');
        expect($output)->toContain('false');
        expect($output)->toContain('boolean');
    });
});
