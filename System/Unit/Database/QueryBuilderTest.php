<?php

declare(strict_types=1);

use Database\ConnectionInterface;
use Database\Query\Builder;
use Database\Query\MySqlGrammar;

beforeEach(function () {
    // Mock connection and grammar for testing
    $this->connection = Mockery::mock(ConnectionInterface::class);
    $this->grammar = new MySqlGrammar('mysql');
    $this->builder = new Builder($this->connection, $this->grammar);
});

afterEach(function () {
    Mockery::close();
});

describe('Query Builder - Basic Operations', function () {
    test('sets table with from method', function () {
        $this->builder->from('users');
        expect($this->builder->getTable())->toBe('users');
    });

    test('selects specific columns', function () {
        $this->builder->select(['id', 'name', 'email']);
        expect($this->builder->getSelects())->toBe(['id', 'name', 'email']);
    });

    test('adds where clause', function () {
        $this->builder->where('status', 'active');
        $wheres = $this->builder->getWheres();

        expect($wheres)->toHaveCount(1);
        expect($wheres[0]['column'])->toBe('status');
        expect($wheres[0]['value'])->toBe('active');
    });

    test('adds multiple where clauses', function () {
        $this->builder->where('status', 'active')
            ->where('age', '>', 18);

        expect($this->builder->getWheres())->toHaveCount(2);
    });

    test('adds or where clause', function () {
        $this->builder->where('status', 'active')
            ->orWhere('status', 'pending');

        $wheres = $this->builder->getWheres();
        expect($wheres[1]['boolean'])->toBe('OR');
    });
});

describe('Query Builder - Advanced Where Clauses', function () {
    test('where in clause', function () {
        $this->builder->whereIn('id', [1, 2, 3]);
        $wheres = $this->builder->getWheres();

        expect($wheres[0]['type'])->toBe('in');
        expect($wheres[0]['values'])->toBe([1, 2, 3]);
    });

    test('where not in clause', function () {
        $this->builder->whereNotIn('status', ['banned', 'suspended']);
        $wheres = $this->builder->getWheres();

        expect($wheres[0]['type'])->toBe('not_in');
    });

    test('where null clause', function () {
        $this->builder->whereNull('deleted_at');
        $wheres = $this->builder->getWheres();

        expect($wheres[0]['type'])->toBe('null');
        expect($wheres[0]['column'])->toBe('deleted_at');
    });

    test('where not null clause', function () {
        $this->builder->whereNotNull('email_verified_at');
        $wheres = $this->builder->getWheres();

        expect($wheres[0]['type'])->toBe('not_null');
    });

    test('where between clause', function () {
        $this->builder->whereBetween('age', [18, 65]);
        $wheres = $this->builder->getWheres();

        expect($wheres[0]['type'])->toBe('between');
        expect($wheres[0]['values'])->toBe([18, 65]);
    });

    test('where like clause', function () {
        $this->builder->whereLike('name', '%John%');
        $wheres = $this->builder->getWheres();

        expect($wheres[0]['operator'])->toBe('LIKE');
    });

    test('nested where clause', function () {
        $this->builder->where(function ($query) {
            $query->where('status', 'active')
                ->orWhere('status', 'pending');
        });

        $wheres = $this->builder->getWheres();
        expect($wheres[0]['type'])->toBe('nested');
    });
});

describe('Query Builder - Joins', function () {
    test('inner join', function () {
        $this->builder->from('users')
            ->join('posts', 'users.id', '=', 'posts.user_id');

        $joins = $this->builder->getJoins();
        expect($joins)->toHaveCount(1);
        expect($joins[0]['type'])->toBe('inner');
    });

    test('left join', function () {
        $this->builder->from('users')
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id');

        $joins = $this->builder->getJoins();
        expect($joins[0]['type'])->toBe('left');
    });

    test('right join', function () {
        $this->builder->from('users')
            ->rightJoin('posts', 'users.id', '=', 'posts.user_id');

        $joins = $this->builder->getJoins();
        expect($joins[0]['type'])->toBe('right');
    });
});

describe('Query Builder - Ordering & Limiting', function () {
    test('order by ascending', function () {
        $this->builder->orderBy('created_at', 'asc');
        $orders = $this->builder->getOrders();

        expect($orders)->toHaveCount(1);
        expect($orders[0]['column'])->toBe('created_at');
        expect(strtoupper($orders[0]['direction']))->toBe('ASC');
    });

    test('order by descending', function () {
        $this->builder->orderBy('created_at', 'desc');
        $orders = $this->builder->getOrders();

        expect(strtoupper($orders[0]['direction']))->toBe('DESC');
    });

    test('limit results', function () {
        $this->builder->limit(10);
        expect($this->builder->getLimit())->toBe(10);
    });

    test('offset results', function () {
        $this->builder->offset(20);
        expect($this->builder->getOffset())->toBe(20);
    });

    test('take is alias for limit', function () {
        $this->builder->take(5);
        expect($this->builder->getLimit())->toBe(5);
    });

    test('skip is alias for offset', function () {
        $this->builder->skip(10);
        expect($this->builder->getOffset())->toBe(10);
    });
});

describe('Query Builder - Grouping & Having', function () {
    test('group by single column', function () {
        $this->builder->groupBy('category');
        expect($this->builder->getGroups())->toBe(['category']);
    });

    test('group by multiple columns', function () {
        $this->builder->groupBy(['category', 'status']);
        expect($this->builder->getGroups())->toBe(['category', 'status']);
    });

    test('having clause', function () {
        $this->builder->having('count', '>', 5);
        $havings = $this->builder->getHavings();

        expect($havings)->toHaveCount(1);
        expect($havings[0]['column'])->toBe('count');
    });
});

describe('Query Builder - Distinct & Locking', function () {
    test('distinct results', function () {
        $this->builder->distinct();
        expect($this->builder->getDistinct())->toBeTrue();
    });

    test('lock for update', function () {
        $this->builder->lockForUpdate();
        expect($this->builder->getForUpdate())->toBeTrue();
        expect($this->builder->getForSharedLock())->toBeFalse();
    });

    test('shared lock', function () {
        $this->builder->lockForSharedReading();
        expect($this->builder->getForSharedLock())->toBeTrue();
        expect($this->builder->getForUpdate())->toBeFalse();
    });
});

describe('Query Builder - Aggregates', function () {
    test('with count eager aggregate', function () {
        $this->builder->withCount('posts');
        $aggregates = $this->builder->getEagerAggregates();

        expect($aggregates)->toHaveKey('posts_count');
        expect($aggregates['posts_count']['function'])->toBe('COUNT');
    });

    test('with sum eager aggregate', function () {
        $this->builder->withSum('orders', 'total');
        $aggregates = $this->builder->getEagerAggregates();

        expect($aggregates)->toHaveKey('orders_sum');
        expect($aggregates['orders_sum']['function'])->toBe('SUM');
    });

    test('with avg eager aggregate', function () {
        $this->builder->withAvg('reviews', 'rating');
        $aggregates = $this->builder->getEagerAggregates();

        expect($aggregates)->toHaveKey('reviews_avg');
    });
});
