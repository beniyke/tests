<?php

declare(strict_types=1);

use Helpers\String\Text;

describe('Text', function () {
    test('wrap wraps text at character limit', function () {
        $text = 'This is a very long line of text that should be wrapped at the specified character limit to make it more readable';
        $result = Text::wrap($text, 40);

        $lines = explode("\n", trim($result));
        foreach ($lines as $line) {
            expect(strlen($line))->toBeLessThanOrEqual(40);
        }
    });

    test('wrap preserves unwrap tags', function () {
        $text = 'Short text {unwrap}This should not be wrapped even if very long{/unwrap} more text';
        $result = Text::wrap($text, 20);

        expect($result)->toContain('This should not be wrapped even if very long');
    });

    test('censor replaces bad words', function () {
        $text = 'This is a badword in the text';
        $result = Text::censor($text, ['badword'], '***');

        expect($result)->toBe('This is a *** in the text');
    });

    test('censor uses hash marks when no replacement given', function () {
        $text = 'This has bad content here';
        $result = Text::censor($text, ['bad']);

        expect($result)->toContain('###');
        expect($result)->not->toContain('bad');
    });

    test('censor handles wildcards', function () {
        $text = 'This has badword and badthing';
        $result = Text::censor($text, ['bad*'], '***');

        expect($result)->toBe('This has *** and ***');
    });

    test('trim truncates text to length', function () {
        $text = 'This is a long piece of text that needs to be trimmed';
        $result = Text::trim($text, 20);

        expect(strlen($result))->toBeLessThanOrEqual(23); // 20 + '...'
        expect($result)->toEndWith('...');
    });

    test('trim without ellipses', function () {
        $text = 'This is a long piece of text';
        $result = Text::trim($text, 15, false);

        expect($result)->not->toEndWith('...');
        expect(strlen($result))->toBeLessThanOrEqual(15);
    });

    test('trim strips HTML by default', function () {
        $text = '<p>This is <strong>HTML</strong> content</p>';
        $result = Text::trim($text, 20);

        expect($result)->not->toContain('<p>');
        expect($result)->not->toContain('<strong>');
    });

    test('trim preserves HTML when requested', function () {
        $text = '<p>Short</p>';
        $result = Text::trim($text, 50, true, false);

        expect($result)->toContain('<p>');
    });

    test('estimated_read_time calculates reading time', function () {
        $content = str_repeat('word ', 200); // 200 words
        $result = Text::estimated_read_time($content, 200);

        expect($result)->toContain('1 minute');
    });

    test('estimated_read_time shows seconds for short content', function () {
        $content = str_repeat('word ', 50); // 50 words
        $result = Text::estimated_read_time($content, 200);

        expect($result)->toContain('second');
    });

    test('estimated_read_time includes seconds when requested', function () {
        $content = str_repeat('word ', 250); // 250 words
        $result = Text::estimated_read_time($content, 200, true);

        expect($result)->toContain('minute');
        expect($result)->toContain('second');
    });

    test('inflect returns singular for count of 1', function () {
        expect(Text::inflect('apple', 1))->toBe('apple');
        expect(Text::inflect('child', 1))->toBe('child');
    });

    test('inflect returns plural for count greater than 1', function () {
        expect(Text::inflect('apple', 5))->toBe('apples');
        expect(Text::inflect('child', 3))->toBe('children');
    });

    test('inflect returns singular for zero', function () {
        expect(Text::inflect('item', 0))->toBe('item');
    });
});
