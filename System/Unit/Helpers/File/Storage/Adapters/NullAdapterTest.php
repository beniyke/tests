<?php

declare(strict_types=1);

namespace Tests\System\Unit\Helpers\File\Storage\Adapters;

use Helpers\File\Storage\Adapters\NullAdapter;

beforeEach(function () {
    $this->adapter = new NullAdapter();
});

test('it properly handles operations', function () {
    expect($this->adapter->exists('any.txt'))->toBeFalse()
        ->and($this->adapter->get('any.txt'))->toBe('')
        ->and($this->adapter->put('any.txt', 'content'))->toBeTrue()
        ->and($this->adapter->delete('any.txt'))->toBeTrue();
});

test('it returns empty lists', function () {
    expect($this->adapter->files())->toBeArray()->toBeEmpty();
});
