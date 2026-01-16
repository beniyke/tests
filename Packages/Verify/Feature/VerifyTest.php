<?php

declare(strict_types=1);

namespace Tests\Packages\Verify\Feature;

/**
 * @property \Database\ConnectionInterface $connection
 */

use Database\DB;
use Helpers\DateTimeHelper;
use Helpers\String\Str;
use Testing\Support\DatabaseTestHelper;
use Verify\Exceptions\OtpExpiredException;
use Verify\Exceptions\OtpInvalidException;
use Verify\Exceptions\RateLimitExceededException;
use Verify\Models\Attempt;
use Verify\Models\OtpCode;
use Verify\Services\OtpGeneratorService;
use Verify\Services\OtpStorageService;
use Verify\Services\RateLimiterService;
use Verify\Verify;

// Set up test database connection before running tests
beforeEach(function () {
    $this->connection = DatabaseTestHelper::setupTestEnvironment(['Verify']);
    $this->bootPackage('Verify');
});

describe('OTP Generator', function () {
    test('generates code with correct length', function () {
        $generator = new OtpGeneratorService();
        $code = $generator->generate(6);

        expect($code)->toBeString()->toHaveLength(6)->toMatch('/^\d{6}$/');
    });

    test('generates different codes', function () {
        $generator = new OtpGeneratorService();
        expect($generator->generate(6))->not->toBe($generator->generate(6));
    });
});

describe('OTP Storage', function () {
    test('can store and verify OTP', function () {
        $storage = new OtpStorageService($this->connection);
        $storage->store('test@example.com', '123456', 'email', 15);

        $result = $storage->verify('test@example.com', '123456');
        expect($result)->toBeTrue();
    });

    test('hashes codes in database', function () {
        $storage = new OtpStorageService($this->connection);
        $storage->store('test@example.com', '123456', 'email', 15);

        $record = OtpCode::query()->where('identifier', 'test@example.com')->first();
        expect($record->code)->not->toBe('123456');
        expect(password_verify('123456', $record->code))->toBeTrue();
    });

    test('throws exception for invalid code', function () {
        $storage = new OtpStorageService($this->connection);
        $storage->store('test@example.com', '123456', 'email', 15);
        $storage->verify('test@example.com', '999999');
    })->throws(OtpInvalidException::class);

    test('throws exception for expired OTP', function () {
        $storage = new OtpStorageService($this->connection);

        Verify::otp('test@example.com')
            ->withCode('123456')
            ->expired(60)
            ->save();

        $storage->verify('test@example.com', '123456');
    })->throws(OtpExpiredException::class);

    test('can cleanup expired codes', function () {
        $storage = new OtpStorageService($this->connection);

        // Add expired code
        Verify::otp('expired@example.com')
            ->withCode('123456')
            ->via('email')
            ->expired(60) // Create an OTP that expired 60 minutes ago
            ->save();

        // Add valid code
        $storage->store('valid@example.com', '654321', 'email', 15);

        $deletedCount = $storage->cleanup();
        expect($deletedCount)->toBe(1);

        $totalCount = DB::table('verify_otp_code')->count();
        expect($totalCount)->toBe(1);
    });

    test('can check if OTP is pending', function () {
        $storage = new OtpStorageService($this->connection);

        // No OTP exists
        expect($storage->hasPending('test@example.com'))->toBeFalse();

        // Store valid OTP
        $storage->store('test@example.com', '123456', 'email', 15);
        expect($storage->hasPending('test@example.com'))->toBeTrue();

        // Verify and mark as verified
        $storage->verify('test@example.com', '123456');
        $storage->markAsVerified('test@example.com');
        expect($storage->hasPending('test@example.com'))->toBeFalse();

        // Store expired OTP
        Verify::otp('expired@example.com')
            ->withCode('999999')
            ->via('email')
            ->expired(60)
            ->save();
        expect($storage->hasPending('expired@example.com'))->toBeFalse();
    });
});

describe('Rate Limiter', function () {
    test('allows operations within limit', function () {
        $limiter = new RateLimiterService($this->connection);
        expect($limiter->checkGeneration('test@example.com', 3, 60))->toBeTrue();
    });

    test('tracks attempts', function () {
        $limiter = new RateLimiterService($this->connection);
        $limiter->incrementGeneration('test@example.com');
        $limiter->incrementGeneration('test@example.com');

        $record = Attempt::query()
            ->where('identifier', 'test@example.com')
            ->where('attempt_type', 'generation')
            ->first();

        expect((int) $record->count)->toBe(2);
    });

    test('throws exception when limit exceeded', function () {
        $limiter = new RateLimiterService($this->connection);

        Attempt::create([
            'identifier' => 'test@example.com',
            'refid' => Str::random('secure'),
            'attempt_type' => 'generation',
            'count' => 5,
            'window_start' => DateTimeHelper::now()->toDateTimeString(),
        ]);

        $limiter->checkGeneration('test@example.com', 3, 60);
    })->throws(RateLimitExceededException::class);
});
