<?php

declare(strict_types=1);

namespace Tests\System\Unit\Security\Auth\Sources;

use Mockery;
use Security\Auth\Contracts\Authenticatable;
use Security\Auth\Sources\DatabaseUserSource;

describe('DatabaseUserSource', function () {
    beforeEach(function () {
        $this->model = Mockery::mock('alias:Tests\System\Unit\Security\Auth\Sources\TestUserModel');
        $this->source = new DatabaseUserSource('Tests\System\Unit\Security\Auth\Sources\TestUserModel');
    });

    afterEach(function () {
        Mockery::close();
    });

    test('retrieveById finds user by ID', function () {
        $user = Mockery::mock(Authenticatable::class);
        $this->model->shouldReceive('find')->with(1)->andReturn($user);

        expect($this->source->retrieveById(1))->toBe($user);
    });

    test('retrieveByCredentials finds user by email', function () {
        $user = Mockery::mock(Authenticatable::class);
        $query = Mockery::mock('Database\Query\Builder');

        $this->model->shouldReceive('query')->andReturn($query);
        $query->shouldReceive('where')->with('email', 'test@example.com')->andReturnSelf();
        $query->shouldReceive('first')->andReturn($user);

        $credentials = ['email' => 'test@example.com', 'password' => 'secret'];
        expect($this->source->retrieveByCredentials($credentials))->toBe($user);
    });

    test('validateCredentials verifies password', function () {
        $user = Mockery::mock(Authenticatable::class);
        $user->shouldReceive('getAuthPassword')->andReturn(password_hash('secret', PASSWORD_DEFAULT));

        $credentials = ['password' => 'secret'];
        expect($this->source->validateCredentials($user, $credentials))->toBeTrue();

        $wrongCredentials = ['password' => 'wrong'];
        expect($this->source->validateCredentials($user, $wrongCredentials))->toBeFalse();
    });
});
