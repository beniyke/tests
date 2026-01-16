<?php

declare(strict_types=1);

use Helpers\Encryption\Drivers\FileEncryptor;
use Helpers\Encryption\Drivers\SymmetricEncryptor;
use Helpers\Encryption\Encrypter;
use Helpers\File\Adapters\Interfaces\FileReadWriteInterface;

describe('Encrypter', function () {
    beforeEach(function () {
        $this->key = base64_encode(random_bytes(32));
        $stringDriver = new SymmetricEncryptor($this->key);

        $fileHandler = Mockery::mock(FileReadWriteInterface::class);
        $fileDriver = new FileEncryptor($fileHandler);
        $fileDriver->password($this->key);

        $this->encrypter = new Encrypter($stringDriver, $fileDriver);
    });

    test('encrypts and decrypts data', function () {
        $data = 'sensitive information';
        $encrypted = $this->encrypter->encrypt($data);

        expect($encrypted)->not->toBe($data);
        expect($this->encrypter->decrypt($encrypted))->toBe($data);
    });

    test('encrypted data is different each time', function () {
        $data = 'test data';
        $encrypted1 = $this->encrypter->encrypt($data);
        $encrypted2 = $this->encrypter->encrypt($data);

        expect($encrypted1)->not->toBe($encrypted2);
    });

    test('decryption fails with wrong key', function () {
        $data = 'secret';
        $encrypted = $this->encrypter->encrypt($data);

        $wrongKey = base64_encode(random_bytes(32));
        $stringDriver = new SymmetricEncryptor($wrongKey);
        $fileHandler = Mockery::mock(FileReadWriteInterface::class);
        $fileDriver = new FileEncryptor($fileHandler);
        $fileDriver->password($wrongKey);

        $wrongEncrypter = new Encrypter($stringDriver, $fileDriver);

        expect(fn () => $wrongEncrypter->decrypt($encrypted))
            ->toThrow(Exception::class);
    });

    test('handles empty strings', function () {
        $encrypted = $this->encrypter->encrypt('');
        expect($this->encrypter->decrypt($encrypted))->toBe('');
    });

    test('handles special characters', function () {
        $data = '!@#$%^&*()_+-=[]{}|;:,.<>?/~`';
        $encrypted = $this->encrypter->encrypt($data);
        expect($this->encrypter->decrypt($encrypted))->toBe($data);
    });

    test('handles unicode characters', function () {
        $data = 'Hello 世界 🌍';
        $encrypted = $this->encrypter->encrypt($data);
        expect($this->encrypter->decrypt($encrypted))->toBe($data);
    });
});

describe('SymmetricEncryptor', function () {
    beforeEach(function () {
        $this->key = base64_encode(random_bytes(32));
        $this->encryptor = new SymmetricEncryptor($this->key);
    });

    test('encrypts and decrypts data', function () {
        $data = 'test data';
        $encrypted = $this->encryptor->encrypt($data);

        expect($encrypted)->not->toBe($data);
        expect($this->encryptor->decrypt($encrypted))->toBe($data);
    });

    test('hashes passwords', function () {
        $password = 'mySecretPassword123!';
        $hash = $this->encryptor->hashPassword($password);

        expect($hash)->not->toBe($password);
        expect($this->encryptor->verifyPassword($password, $hash))->toBeTrue();
        expect($this->encryptor->verifyPassword('wrongPassword', $hash))->toBeFalse();
    });

    test('throws exception for invalid key length', function () {
        $invalidKey = base64_encode('short');

        expect(fn () => new SymmetricEncryptor($invalidKey))
            ->toThrow(InvalidArgumentException::class);
    });

    test('detects tampered data', function () {
        $data = 'important data';
        $encrypted = $this->encryptor->encrypt($data);

        // Tamper with the encrypted data
        $tampered = substr($encrypted, 0, -5).'XXXXX';

        expect(fn () => $this->encryptor->decrypt($tampered))
            ->toThrow(Exception::class);
    });
});
