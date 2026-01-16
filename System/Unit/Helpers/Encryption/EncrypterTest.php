<?php

declare(strict_types=1);

use Helpers\Encryption\Drivers\FileEncryptor;
use Helpers\Encryption\Drivers\SymmetricEncryptorInterface;
use Helpers\Encryption\Encrypter;
use Mockery as m;

describe('Encrypter', function () {
    beforeEach(function () {
        $this->stringDriver = m::mock(SymmetricEncryptorInterface::class);
        $this->fileDriver = m::mock(FileEncryptor::class);
        $this->encrypter = new Encrypter($this->stringDriver, $this->fileDriver);
    });

    afterEach(function () {
        m::close();
    });

    test('defaults to string driver', function () {
        $this->stringDriver->shouldReceive('encrypt')
            ->with('data')
            ->once()
            ->andReturn('encrypted');

        expect($this->encrypter->encrypt('data'))->toBe('encrypted');
    });

    test('can switch to file driver', function () {
        $this->fileDriver->shouldReceive('encrypt')
            ->with('source', 'dest')
            ->once();

        $this->encrypter->file()->encrypt('source', 'dest');

        expect(true)->toBeTrue(); // Avoid risky test
    });

    test('can switch back to string driver', function () {
        $this->stringDriver->shouldReceive('decrypt')
            ->with('payload')
            ->once()
            ->andReturn('data');

        $this->encrypter->file()->string()->decrypt('payload');

        expect(true)->toBeTrue(); // Avoid risky test
    });

    test('throws exception for missing method', function () {
        expect(fn () => $this->encrypter->nonExistentMethod())->toThrow(BadMethodCallException::class);
    });
});
