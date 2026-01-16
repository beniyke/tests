<?php

declare(strict_types=1);

namespace Tests\Packages\Bridge\Integration;

use Bridge\Models\ApiKey;

describe('API Key Generation', function () {

    beforeEach(function () {
        $this->bootPackage('Bridge', runMigrations: true);
    });



    test('generate method creates api key', function () {
        $result = ApiKey::generate('Test Key');
        $apiKey = $result['model'];

        expect($apiKey->id)->not->toBeNull()
            ->and($apiKey->name)->toBe('Test Key')
            ->and($apiKey->key)->not->toBeNull();

        // Verify it was saved to the database
        $found = ApiKey::find($apiKey->id);
        expect($found)->not->toBeNull()
            ->and($found->name)->toBe('Test Key');
    });

    test('generated key is hashed in database', function () {
        $result = ApiKey::generate('Test Key');
        $rawKey = $result['key'];

        // Verify the stored key is a hash, not the raw key
        expect($result['model']->key)->not->toBe($rawKey);
        expect($result['model']->key)->toBe(hash('sha256', $rawKey));
    });

    test('revoke method deletes api key', function () {
        $result = ApiKey::generate('Test Key');
        $apiKey = $result['model'];

        expect($apiKey->revoke())->toBeTrue();

        // Verify it's deleted
        $found = ApiKey::find($apiKey->id);
        expect($found)->toBeNull();
    });

    test('multiple keys can be generated', function () {
        $result1 = ApiKey::generate('Key 1');
        $result2 = ApiKey::generate('Key 2');

        expect($result1['key'])->not->toBe($result2['key']);
        expect($result1['model']->id)->not->toBe($result2['model']->id);
    });
});
