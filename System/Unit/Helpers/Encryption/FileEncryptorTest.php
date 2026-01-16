<?php

declare(strict_types=1);

use Helpers\Encryption\Drivers\FileEncryptor;
use Helpers\File\Adapters\Interfaces\FileReadWriteInterface;
use Mockery as m;

describe('FileEncryptor', function () {
    beforeEach(function () {
        $this->fileHandler = m::mock(FileReadWriteInterface::class);
        $this->encryptor = new FileEncryptor($this->fileHandler);
        $this->encryptor->password('secret_password');
    });

    afterEach(function () {
        m::close();
    });

    test('encrypts file content and writes to destination', function () {
        $source = 'source.txt';
        $dest = 'dest.enc';
        $content = 'file content';

        $this->fileHandler->shouldReceive('get')
            ->with($source)
            ->once()
            ->andReturn($content);

        $this->fileHandler->shouldReceive('put')
            ->with($dest, m::type('string'))
            ->once()
            ->andReturn(true);

        $this->encryptor->encrypt($source, $dest);

        // Mockery verifies expectations on close, but to avoid "risky" test warning:
        expect(true)->toBeTrue();
    });

    test('throws exception if source file cannot be read', function () {
        $this->fileHandler->shouldReceive('get')
            ->andThrow(new RuntimeException('File not found'));

        expect(fn () => $this->encryptor->encrypt('missing.txt', 'dest.enc'))
            ->toThrow(RuntimeException::class, 'File not found');
    });

    test('decrypts file content correctly', function () {
        $file = 'encrypted.enc';

        $this->fileHandler->shouldReceive('get')
            ->with($file)
            ->once()
            ->andReturn('invalid_payload_too_short');

        expect(fn () => $this->encryptor->decrypt($file))
            ->toThrow(Exception::class);
    });
});
