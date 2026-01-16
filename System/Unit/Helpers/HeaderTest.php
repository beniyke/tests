<?php

declare(strict_types=1);

use Helpers\Http\Header;

describe('Header', function () {

    test('constructor sets default headers', function () {
        $header = new Header();

        expect($header->has('Content-Type'))->toBeTrue();
        expect($header->has('Cache-Control'))->toBeTrue();
        expect($header->has('Date'))->toBeTrue();
    });

    test('constructor accepts custom headers', function () {
        $header = new Header(['X-Custom' => 'value']);

        expect($header->get('X-Custom'))->toBe('value');
    });

    test('set stores header with formatted key', function () {
        $header = new Header();
        $header->set('x-custom-header', 'value');

        expect($header->get('X-Custom-Header'))->toBe('value');
    });

    test('set returns self for chaining', function () {
        $header = new Header();
        $result = $header->set('X-Test', 'value');

        expect($result)->toBe($header);
    });

    test('get returns header value', function () {
        $header = new Header();
        $header->set('X-Test', 'value');

        expect($header->get('X-Test'))->toBe('value');
    });

    test('get returns default when header does not exist', function () {
        $header = new Header();

        expect($header->get('X-Nonexistent', 'default'))->toBe('default');
    });

    test('has returns true when header exists', function () {
        $header = new Header();
        $header->set('X-Test', 'value');

        expect($header->has('X-Test'))->toBeTrue();
    });

    test('has returns false when header does not exist', function () {
        $header = new Header();

        expect($header->has('X-Nonexistent'))->toBeFalse();
    });

    test('all returns all headers', function () {
        $header = new Header();
        $headers = $header->all();

        expect($headers)->toBeArray();
        expect($headers)->toHaveKey('Content-Type');
    });

    test('remove deletes header', function () {
        $header = new Header();
        $header->set('X-Test', 'value');
        $header->remove('X-Test');

        expect($header->has('X-Test'))->toBeFalse();
    });

    test('formatHeader handles uppercase headers', function () {
        $header = new Header();
        $header->set('ACCEPT', 'application/json');

        expect($header->get('ACCEPT'))->toBe('application/json');
    });

    test('formatHeader capitalizes words', function () {
        $header = new Header();
        $header->set('content-type', 'text/html');

        expect($header->has('Content-Type'))->toBeTrue();
    });

    test('formatHeader handles multiple dashes', function () {
        $header = new Header();
        $header->set('x-custom-long-header', 'value');

        expect($header->get('X-Custom-Long-Header'))->toBe('value');
    });

    test('set trims string values', function () {
        $header = new Header();
        $header->set('X-Test', '  value  ');

        expect($header->get('X-Test'))->toBe('value');
    });
});
