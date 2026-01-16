<?php

declare(strict_types=1);

use Helpers\Encryption\Drivers\SymmetricEncryptor;

describe('Graceful Key Rotation', function () {
    // Generate valid 32-byte keys (base64 encoded)
    $keyA = base64_encode(str_repeat('A', 32));
    $keyB = base64_encode(str_repeat('B', 32));
    $keyC = base64_encode(str_repeat('C', 32));

    test('decrypts data encrypted with a previous key', function () use ($keyA, $keyB) {
        $encryptorA = new SymmetricEncryptor($keyA);
        $payload = $encryptorA->encrypt('sensitive data');

        // Initialize with Key B as primary, Key A as previous
        $encryptorB = new SymmetricEncryptor($keyB, [$keyA]);

        expect($encryptorB->decrypt($payload))->toBe('sensitive data');
    });

    test('fails if previous key is missing', function () use ($keyA, $keyB, $keyC) {
        $encryptorA = new SymmetricEncryptor($keyA);
        $payload = $encryptorA->encrypt('sensitive data');

        // Initialize with Key B as primary, Key C as previous (Key A missing)
        $encryptorB = new SymmetricEncryptor($keyB, [$keyC]);

        expect(fn () => $encryptorB->decrypt($payload))->toThrow(RuntimeException::class);
    });

    test('new data is encrypted with the primary key', function () use ($keyA, $keyB) {
        // Initialize with Key B as primary, Key A as previous
        $encryptorB = new SymmetricEncryptor($keyB, [$keyA]);
        $payload = $encryptorB->encrypt('new data');

        // Key A should fail to decrypt it
        $encryptorA = new SymmetricEncryptor($keyA);
        expect(fn () => $encryptorA->decrypt($payload))->toThrow(RuntimeException::class);

        // Key B should decrypt it
        $freshEncryptorB = new SymmetricEncryptor($keyB);
        expect($freshEncryptorB->decrypt($payload))->toBe('new data');
    });

    test('handles multiple previous keys', function () use ($keyA, $keyB, $keyC) {
        $encryptorA = new SymmetricEncryptor($keyA);
        $payloadA = $encryptorA->encrypt('data A');

        $encryptorB = new SymmetricEncryptor($keyB);
        $payloadB = $encryptorB->encrypt('data B');

        // Key C is primary, [Key A, Key B] are previous
        $encryptorC = new SymmetricEncryptor($keyC, [$keyA, $keyB]);

        expect($encryptorC->decrypt($payloadA))->toBe('data A');
        expect($encryptorC->decrypt($payloadB))->toBe('data B');
    });
});
