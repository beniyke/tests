<?php

declare(strict_types=1);

use Helpers\File\Paths;

describe('Paths', function () {

    beforeEach(function () {
        Paths::setBasePath(getcwd());
    });

    test('basePath returns base path', function () {
        $cwd = getcwd();
        expect(Paths::basePath())->toContain($cwd);
    });

    test('basePath appends value', function () {
        $path = Paths::basePath('subfolder');
        expect($path)->toContain('subfolder');
    });

    test('appPath returns app directory', function () {
        $path = Paths::appPath();
        expect($path)->toContain('App');
    });

    test('appPath appends value', function () {
        $path = Paths::appPath('Models');
        expect($path)->toContain('App');
        expect($path)->toContain('Models');
    });

    test('systemPath returns system directory', function () {
        $path = Paths::systemPath();
        expect($path)->toContain('System');
    });

    test('systemPath appends value', function () {
        $path = Paths::systemPath('Core');
        expect($path)->toContain('System');
        expect($path)->toContain('Core');
    });

    test('appSourcePath returns app src directory', function () {
        $path = Paths::appSourcePath();
        expect($path)->toContain('App');
        expect($path)->toContain('src');
    });

    test('testPath returns tests directory', function () {
        $path = Paths::testPath();
        expect($path)->toContain('tests');
    });

    test('corePath returns core directory', function () {
        $path = Paths::corePath();
        expect($path)->toContain('System');
        expect($path)->toContain('Core');
    });

    test('cliPath returns cli directory', function () {
        $path = Paths::cliPath();
        expect($path)->toContain('System');
        expect($path)->toContain('Cli');
    });

    test('configPath returns config directory', function () {
        $path = Paths::configPath();
        expect($path)->toContain('App');
        expect($path)->toContain('Config');
    });

    test('publicPath returns public directory', function () {
        $path = Paths::publicPath();
        expect($path)->toContain('public');
    });

    test('storagePath returns storage directory', function () {
        $path = Paths::storagePath();
        expect($path)->toContain('App');
        expect($path)->toContain('storage');
    });

    test('cachePath returns cache directory', function () {
        $path = Paths::cachePath();
        expect($path)->toContain('storage');
        expect($path)->toContain('cache');
    });

    test('viewPath returns views directory', function () {
        $path = Paths::viewPath();
        expect($path)->toContain('App');
        expect($path)->toContain('Views');
    });

    test('viewPath with module returns module views directory', function () {
        $path = Paths::viewPath(null, 'admin');
        expect($path)->toContain('Admin');
        expect($path)->toContain('Views');
    });

    test('layoutPath returns layouts directory', function () {
        $path = Paths::layoutPath();
        expect($path)->toContain('Views');
        expect($path)->toContain('Templates');
        expect($path)->toContain('layouts');
    });

    test('templatePath returns templates directory', function () {
        $path = Paths::templatePath();
        expect($path)->toContain('Views');
        expect($path)->toContain('Templates');
    });

    test('join combines paths', function () {
        $path = Paths::join('folder1', 'folder2', 'file.txt');
        expect($path)->toContain('folder1');
        expect($path)->toContain('folder2');
        expect($path)->toContain('file.txt');
    });

    test('normalize replaces slashes with directory separator', function () {
        $path = Paths::normalize('folder1/folder2\folder3');
        $separator = DIRECTORY_SEPARATOR;
        expect($path)->toBe("folder1{$separator}folder2{$separator}folder3");
    });

    test('normalize removes duplicate separators', function () {
        $sep = DIRECTORY_SEPARATOR;
        $path = Paths::normalize("folder1{$sep}{$sep}folder2");
        expect($path)->toBe("folder1{$sep}folder2");
    });

    test('basename returns file name', function () {
        $name = Paths::basename('/path/to/file.txt');
        expect($name)->toBe('file.txt');
    });

    test('dirname returns directory path', function () {
        $dir = Paths::dirname('/path/to/file.txt');
        expect($dir)->toContain('path');
        expect($dir)->toContain('to');
    });
});
