<?php

declare(strict_types=1);

use Helpers\Encryption\Drivers\SymmetricEncryptor;

describe('SymmetricEncryptor', function () {
    // 32-byte key for AES-256
    $key = base64_encode(str_repeat('a', 32));
    $encryptor = new SymmetricEncryptor($key);

    test('encrypts and decrypts string correctly', function () use ($encryptor) {
        $original = 'secret message';
        $encrypted = $encryptor->encrypt($original);

        expect($encrypted)->not->toBe($original);
        expect($encryptor->decrypt($encrypted))->toBe($original);
    });

    test('throws exception for invalid key length', function () {
        $invalidKey = base64_encode('short_key');
        new SymmetricEncryptor($invalidKey);
    })->throws(InvalidArgumentException::class, 'Encryption key must be a 32-byte');

    test('throws exception for invalid payload', function () use ($encryptor) {
        $encryptor->decrypt('invalid_base64_payload');
    })->throws(InvalidArgumentException::class);

    test('throws exception for tampered payload', function () use ($encryptor) {
        $original = 'secret';
        $encrypted = $encryptor->encrypt($original);

        // Tamper with the encrypted string (after base64 decode)
        $decoded = base64_decode($encrypted);
        $tampered = base64_encode(substr($decoded, 0, -1).'X');

        $encryptor->decrypt($tampered);
    })->throws(RuntimeException::class);

    test('hashes and verifies passwords', function () use ($encryptor) {
        $password = 'my_password';
        $hash = $encryptor->hashPassword($password);

        expect($hash)->not->toBe($password);
        expect($encryptor->verifyPassword($password, $hash))->toBeTrue();
        expect($encryptor->verifyPassword('wrong_password', $hash))->toBeFalse();
    });
});
