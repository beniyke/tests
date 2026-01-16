<?php

declare(strict_types=1);

use Database\ConnectionInterface;
use Database\Query\Builder;
use Database\Query\MySqlGrammar;

beforeEach(function () {
    $this->connection = Mockery::mock(ConnectionInterface::class);
    $this->grammar = new MySqlGrammar('mysql');
    $this->builder = new Builder($this->connection, $this->grammar);
});

afterEach(function () {
    Mockery::close();
});

describe('Query Builder - Extended Operations', function () {
    test('increment each', function () {
        $this->builder->from('users');

        $this->connection->shouldReceive('update')
            ->once()
            ->withArgs(function ($sql, $bindings) {
                return str_contains($sql, '`votes` = `votes` + 1') &&
                    str_contains($sql, '`balance` = `balance` + 100');
            })
            ->andReturn(1);

        $this->builder->incrementEach([
            'votes' => 1,
            'balance' => 100,
        ]);
    });

    test('decrement each', function () {
        $this->builder->from('users');

        $this->connection->shouldReceive('update')
            ->once()
            ->withArgs(function ($sql, $bindings) {
                return str_contains($sql, '`votes` = `votes` - 1') &&
                    str_contains($sql, '`balance` = `balance` - 50');
            })
            ->andReturn(1);

        $this->builder->decrementEach([
            'votes' => 1,
            'balance' => 50,
        ]);
    });

    test('pluck returns array of values', function () {
        $this->builder->from('users');

        $this->connection->shouldReceive('select')
            ->once()
            ->andReturn([
                ['name' => 'John'],
                ['name' => 'Jane'],
            ]);

        $names = $this->builder->pluck('name');
        expect($names)->toBe(['John', 'Jane']);
    });

    test('value returns single column from first row', function () {
        $this->builder->from('users');

        $this->connection->shouldReceive('select')
            ->once()
            ->andReturn([
                ['email' => 'john@example.com'],
            ]);

        $email = $this->builder->value('email');
        expect($email)->toBe('john@example.com');
    });

    test('find returns record by id', function () {
        $this->builder->from('users');

        $this->connection->shouldReceive('select')
            ->once()
            ->with('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', [1])
            ->andReturn([['id' => 1, 'name' => 'John']]);

        $user = $this->builder->find(1);
        expect($user)->toBeObject();
        expect($user->id)->toBe(1);
    });

    test('where regexp', function () {
        $this->builder->whereRegexp('name', '^[A-Z]');
        $wheres = $this->builder->getWheres();

        expect($wheres[0]['type'])->toBe('raw');
        expect($wheres[0]['sql'])->toContain('REGEXP');
    });

    test('max aggregate', function () {
        $this->builder->from('orders');
        $this->connection->shouldReceive('select')->once()->andReturn([['aggregate' => 100]]);
        expect($this->builder->max('amount'))->toBe(100);
    });

    test('min aggregate', function () {
        $this->builder->from('orders');
        $this->connection->shouldReceive('select')->once()->andReturn([['aggregate' => 50]]);
        expect($this->builder->min('amount'))->toBe(50);
    });

    test('sum aggregate', function () {
        $this->builder->from('orders');
        $this->connection->shouldReceive('select')->once()->andReturn([['aggregate' => 1000]]);
        expect($this->builder->sum('amount'))->toBe(1000);
    });

    test('avg aggregate', function () {
        $this->builder->from('orders');
        $this->connection->shouldReceive('select')->once()->andReturn([['aggregate' => 75]]);
        expect($this->builder->avg('amount'))->toBe(75);
    });

    test('date helpers', function () {
        $this->builder->whereOnOrAfter('created_at', '2024-01-01');
        $wheres = $this->builder->getWheres();
        expect($wheres[0]['operator'])->toBe('>=');

        $this->builder->whereOnOrBefore('created_at', '2024-01-01');
        $wheres = $this->builder->getWheres();
        expect($wheres[1]['operator'])->toBe('<=');
    });

    test('ordering helpers', function () {
        $this->builder->latest();
        $orders = $this->builder->getOrders();
        expect($orders[0]['column'])->toBe('created_at');
        expect(strtoupper($orders[0]['direction']))->toBe('DESC');

        $this->builder->oldest();
        $orders = $this->builder->getOrders();
        expect($orders[1]['column'])->toBe('created_at');
        expect(strtoupper($orders[1]['direction']))->toBe('ASC');

        $this->builder->inRandomOrder();
        $orders = $this->builder->getOrders();
        expect($orders[2]['column'])->toBeInstanceOf(Database\Query\RawExpression::class);
        expect($orders[2]['column']->getExpression())->toBe('RAND()');
    });

    test('select raw', function () {
        $this->builder->selectRaw('count(*) as count');
        $selects = $this->builder->getSelects();
        expect($selects[1])->toBeInstanceOf(Database\Query\RawExpression::class);
        expect($selects[1]->getExpression())->toBe('count(*) as count');
    });

    test('having raw', function () {
        $this->builder->havingRaw('count > ?', [5]);
        $havings = $this->builder->getHavings();
        expect($havings[0]['type'])->toBe('raw');
        expect($havings[0]['sql'])->toBe('count > ?');
    });

    test('or having', function () {
        $this->builder->having('count', '>', 5)
            ->orHaving('count', '<', 2);
        $havings = $this->builder->getHavings();
        expect($havings[1]['boolean'])->toBe('OR');
    });

    test('pagination helpers', function () {
        $this->builder->from('users');
        $this->builder->forPage(2, 20);
        expect($this->builder->getLimit())->toBe(20);
        expect($this->builder->getOffset())->toBe(20);

        // Mock for isPageValid - needs to mock count query with aggregate key
        $this->connection->shouldReceive('select')->times(2)->andReturn([['aggregate' => 50]]);

        $this->builder->from('users');
        expect($this->builder->isPageValid(2, 20))->toBeTrue();

        $this->builder->from('users');
        expect($this->builder->isPageValid(4, 20))->toBeFalse();
    });

    test('debugging helpers', function () {
        $this->builder->from('users')->where('id', 1);
        expect($this->builder->toSql())->toBeString();
        expect($this->builder->getBindings())->toBe([1]);
    });

    test('restore soft deleted', function () {
        $this->builder->setModelClass(DummyModelWithSoftDeletes::class);
        $this->builder->from('users');

        $this->connection->shouldReceive('update')
            ->once()
            ->withArgs(function ($sql, $bindings) {
                return str_contains($sql, '`deleted_at` = ?') && $bindings[0] === null;
            })
            ->andReturn(1);

        $this->builder->restore();
    });

    test('eager loading with', function () {
        $this->builder->with(['posts', 'comments']);
        expect($this->builder->getEagerLoads())->toBe(['posts', 'comments']);
    });
});

class DummyModelWithSoftDeletes
{
    use Database\Traits\SoftDeletes;
    public const SOFT_DELETE_COLUMN = 'deleted_at';

    public function getUpdatedAtColumn()
    {
        return 'updated_at';
    }

    // Abstract methods implementation
    public function usesSoftDeletes(): bool
    {
        return true;
    }

    public function getPrimaryKey(): string
    {
        return 'id';
    }

    protected function fireEvent(string $event): bool
    {
        return true;
    }

    public static function query(): Builder
    {
        return Mockery::mock(Builder::class);
    }

    public function castAttributeOnSet(string $key, mixed $value): mixed
    {
        return $value;
    }

    public static function addGlobalScope(string $identifier, callable $scope): void
    {
    }
}
