<?php

declare(strict_types=1);

use Mail\Core\EmailComponent;

describe('EmailComponent - Markdown Support', function () {
    test('renders markdown bold', function () {
        $html = EmailComponent::make()
            ->markdown('This is **bold** text.')
            ->render();

        expect($html)->toContain('<strong>bold</strong>');
        expect($html)->toContain('<p style="font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 15px;">');
    });

    test('renders markdown italics', function () {
        $html = EmailComponent::make()
            ->markdown('This is *italic* text.')
            ->render();

        expect($html)->toContain('<em>italic</em>');
    });

    test('renders markdown links', function () {
        $html = EmailComponent::make()
            ->markdown('Check [this link](https://example.com).')
            ->render();

        expect($html)->toContain('<a href="https://example.com">this link</a>');
    });

    test('escapes HTML when escape is enabled', function () {
        $html = EmailComponent::make(true)
            ->markdown('Test <script>alert(1)</script> **bold**')
            ->render();

        expect($html)->not->toContain('<script>');
        expect($html)->toContain('&lt;script&gt;');
        expect($html)->toContain('<strong>bold</strong>');
    });

    test('allows HTML when escape is disabled', function () {
        $html = EmailComponent::make(false)
            ->markdown('Test <span>html</span> **bold**')
            ->render();

        expect($html)->toContain('<span>html</span>');
        expect($html)->toContain('<strong>bold</strong>');
    });
});
