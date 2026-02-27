<?php

declare(strict_types=1);

use Core\Route\Route;

test('simple exclusive route bypasses auth', function () {
    Route::exclusive()->get('debug-exclusive', function () {
        return 'exclusive-works';
    });

    $this->get('debug-exclusive');
    $this->assertOk();
    $this->assertSee('exclusive-works');
});

test('simple non-exclusive route might also work if no pattern matches', function () {
    Route::get('debug-normal', function () {
        return 'normal-works';
    });

    $this->get('debug-normal');
    $this->assertOk();
    $this->assertSee('normal-works');
});
