<?php

declare(strict_types=1);

use Helpers\String\Str;

test('random generates string of correct length', function () {
    $result = Str::random('alnum', 32);
    expect($result)->toBeString()->toHaveLength(32);
});

test('random generates different strings', function () {
    $first = Str::random('alnum', 16);
    $second = Str::random('alnum', 16);
    expect($first)->not->toBe($second);
});

test('random supports different types', function () {
    expect(Str::random('alpha', 10))->toMatch('/^[a-zA-Z]+$/');
    expect(Str::random('numeric', 10))->toMatch('/^[0-9]+$/');
    expect(Str::random('nozero', 10))->toMatch('/^[1-9]+$/');
});

test('password generates correct length', function () {
    $password = Str::password(20);
    expect($password)->toHaveLength(20);
});

test('htmlEscape prevents XSS', function () {
    $input = '<script>alert("xss")</script>';
    $escaped = Str::htmlEscape($input);
    expect($escaped)->toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
});

test('slug creates URL-friendly string', function () {
    expect(Str::slug('Hello World!'))->toBe('hello-world');
    expect(Str::slug('Café Münchën'))->toBe('cafe-munchen');
});

test('contains checks substring', function () {
    expect(Str::contains('Hello World', 'World'))->toBeTrue();
    expect(Str::contains('Hello World', 'world'))->toBeFalse();
    expect(Str::contains('Hello World', 'world', true))->toBeTrue();
});

test('startsWith checks prefix', function () {
    expect(Str::startsWith('Hello World', 'Hello'))->toBeTrue();
    expect(Str::startsWith('Hello World', 'World'))->toBeFalse();
});

test('endsWith checks suffix', function () {
    expect(Str::endsWith('Hello World', 'World'))->toBeTrue();
    expect(Str::endsWith('Hello World', 'Hello'))->toBeFalse();
});

test('limit truncates string', function () {
    $text = 'The quick brown fox jumps over the lazy dog';
    expect(Str::limit($text, 20))->toBe('The quick brown fox...');
});

test('ucfirst capitalizes first letter', function () {
    expect(Str::ucfirst('hello world'))->toBe('Hello world');
});

test('title converts to title case', function () {
    expect(Str::title('hello world'))->toBe('Hello World');
});

test('camel converts to camelCase', function () {
    expect(Str::camel('hello_world'))->toBe('helloWorld');
    expect(Str::camel('hello-world'))->toBe('helloWorld');
});

test('snake converts to snake_case', function () {
    expect(Str::snake('HelloWorld'))->toBe('hello_world');
    expect(Str::snake('helloWorld'))->toBe('hello_world');
});

test('kebab converts to kebab-case', function () {
    expect(Str::kebab('HelloWorld'))->toBe('hello-world');
    expect(Str::kebab('helloWorld'))->toBe('hello-world');
});

test('studly converts to StudlyCase', function () {
    expect(Str::studly('hello_world'))->toBe('HelloWorld');
    expect(Str::studly('hello-world'))->toBe('HelloWorld');
});

test('replace replaces substring', function () {
    expect(Str::replace('Hello World', 'World', 'PHP'))->toBe('Hello PHP');
});

test('replaceArray replaces with array', function () {
    $result = Str::replaceArray('?', ['foo', 'bar'], '? and ?');
    expect($result)->toBe('foo and bar');
});

test('after returns substring after needle', function () {
    expect(Str::after('Hello World', 'Hello '))->toBe('World');
});

test('before returns substring before needle', function () {
    expect(Str::before('Hello World', ' World'))->toBe('Hello');
});

test('between returns substring between two strings', function () {
    expect(Str::between('[Hello World]', '[', ']'))->toBe('Hello World');
});

test('length returns correct length', function () {
    expect(Str::length('Hello'))->toBe(5);
    expect(Str::length('Café'))->toBe(4);
});

test('lower converts to lowercase', function () {
    expect(Str::lower('HELLO WORLD'))->toBe('hello world');
});

test('upper converts to uppercase', function () {
    expect(Str::upper('hello world'))->toBe('HELLO WORLD');
});

test('substr extracts substring', function () {
    expect(Str::substr('Hello World', 0, 5))->toBe('Hello');
    expect(Str::substr('Hello World', 6))->toBe('World');
});

test('words limits word count', function () {
    $text = 'The quick brown fox jumps over the lazy dog';
    expect(Str::words($text, 3))->toBe('The quick brown...');
});

test('finish ensures string ends with value', function () {
    expect(Str::finish('path/to', '/'))->toBe('path/to/');
    expect(Str::finish('path/to/', '/'))->toBe('path/to/');
});

test('start ensures string starts with value', function () {
    expect(Str::start('path/to', '/'))->toBe('/path/to');
    expect(Str::start('/path/to', '/'))->toBe('/path/to');
});

test('is matches pattern', function () {
    expect(Str::is('foo*', 'foobar'))->toBeTrue();
    expect(Str::is('foo*', 'barfoo'))->toBeFalse();
});

test('mask masks portion of string', function () {
    expect(Str::mask('john@example.com', '*', 4, 10))->toBe('john**********om');
});

test('padBoth pads string on both sides', function () {
    expect(Str::padBoth('Hello', 11, '-'))->toBe('---Hello---');
});

test('padLeft pads string on left', function () {
    expect(Str::padLeft('Hello', 10, '-'))->toBe('-----Hello');
});

test('padRight pads string on right', function () {
    expect(Str::padRight('Hello', 10, '-'))->toBe('Hello-----');
});

test('repeat repeats string', function () {
    expect(Str::repeat('ab', 3))->toBe('ababab');
});

test('reverse reverses string', function () {
    expect(Str::reverse('Hello'))->toBe('olleH');
});

test('swap swaps substrings', function () {
    $result = Str::swap(['foo' => 'bar', 'bar' => 'foo'], 'foo bar');
    expect($result)->toBe('bar foo');
});
