<?php

declare(strict_types=1);

use Helpers\File\Mimes;

describe('Mimes', function () {

    test('guessTypeFromExtension returns mime type for known extension', function () {
        $mime = Mimes::guessTypeFromExtension('jpg');
        expect($mime)->toBe('image/jpeg');
    });

    test('guessTypeFromExtension returns first mime type for array', function () {
        $mime = Mimes::guessTypeFromExtension('png');
        expect($mime)->toBe('image/png');
    });

    test('guessTypeFromExtension returns null for unknown extension', function () {
        $mime = Mimes::guessTypeFromExtension('unknownext');
        expect($mime)->toBeNull();
    });

    test('guessTypeFromExtension trims dots and spaces', function () {
        $mime = Mimes::guessTypeFromExtension('.jpg ');
        expect($mime)->toBe('image/jpeg');
    });

    test('guessTypeFromExtension is case insensitive', function () {
        $mime = Mimes::guessTypeFromExtension('JPG');
        expect($mime)->toBe('image/jpeg');
    });

    test('guessExtensionFromType returns extension for known mime type', function () {
        $ext = Mimes::guessExtensionFromType('image/jpeg');
        expect($ext)->toBe('jpg');
    });

    test('guessExtensionFromType returns null for unknown mime type', function () {
        $ext = Mimes::guessExtensionFromType('unknown/type');
        expect($ext)->toBeNull();
    });

    test('guessExtensionFromType validates proposed extension', function () {
        $ext = Mimes::guessExtensionFromType('image/jpeg', 'jpeg');
        expect($ext)->toBe('jpeg');
    });

    test('guessExtensionFromType returns null for invalid proposed extension', function () {
        $ext = Mimes::guessExtensionFromType('image/jpeg', 'png');
        expect($ext)->toBeNull();
    });

    test('guessExtensionFromType is case insensitive', function () {
        $ext = Mimes::guessExtensionFromType('IMAGE/JPEG');
        expect($ext)->toBe('jpg');
    });

    test('extractExtension returns file extension', function () {
        $ext = Mimes::extractExtension('document.pdf');
        expect($ext)->toBe('pdf');
    });

    test('extractExtension handles paths', function () {
        $ext = Mimes::extractExtension('/path/to/file.txt');
        expect($ext)->toBe('txt');
    });

    test('extractExtension handles multiple dots', function () {
        $ext = Mimes::extractExtension('archive.tar.gz');
        expect($ext)->toBe('gz');
    });

    test('guessTypeFromExtension handles pdf', function () {
        $mime = Mimes::guessTypeFromExtension('pdf');
        expect($mime)->toBe('application/pdf');
    });

    test('guessTypeFromExtension handles zip', function () {
        $mime = Mimes::guessTypeFromExtension('zip');
        expect($mime)->toBe('application/x-zip');
    });

    test('guessTypeFromExtension handles json', function () {
        $mime = Mimes::guessTypeFromExtension('json');
        expect($mime)->toBe('application/json');
    });

    test('guessTypeFromExtension handles xml', function () {
        $mime = Mimes::guessTypeFromExtension('xml');
        expect($mime)->toBe('application/xml');
    });

    test('guessTypeFromExtension handles mp4', function () {
        $mime = Mimes::guessTypeFromExtension('mp4');
        expect($mime)->toBe('video/mp4');
    });

    test('guessTypeFromExtension handles mp3', function () {
        $mime = Mimes::guessTypeFromExtension('mp3');
        expect($mime)->toBe('audio/mpeg');
    });

    test('guessExtensionFromType handles application/json', function () {
        $ext = Mimes::guessExtensionFromType('application/json');
        expect($ext)->toBe('json');
    });

    test('guessExtensionFromType handles text/plain', function () {
        $ext = Mimes::guessExtensionFromType('text/plain');
        expect($ext)->toBeIn(['txt', 'csv', 'log', 'js', 'css', 'html', 'htm', 'shtml', 'xml', 'm3u', 'srt', 'vtt']);
    });
});
