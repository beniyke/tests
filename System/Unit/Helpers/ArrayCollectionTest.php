<?php

declare(strict_types=1);

use Helpers\Array\ArrayCollection;

describe('ArrayCollection', function () {

    test('set sets value using dot notation', function () {
        $array = ['a' => ['b' => 'c']];
        $result = ArrayCollection::set($array, 'a.b', 'd');
        expect($result['a']['b'])->toBe('d');

        $result = ArrayCollection::set($array, 'x.y', 'z');
        expect($result['x']['y'])->toBe('z');
    });

    test('value gets value using dot notation', function () {
        $array = ['a' => ['b' => 'c']];
        expect(ArrayCollection::value($array, 'a.b'))->toBe('c');
        expect(ArrayCollection::value($array, 'a.x'))->toBeNull();
        expect(ArrayCollection::value($array, 'a.x', 'default'))->toBe('default');
    });

    test('getOrSet returns existing value without modification', function () {
        $array = ['name' => 'John'];
        $result = ArrayCollection::getOrSet($array, 'name', 'Jane');
        expect($result)->toBe('John');
        expect($array['name'])->toBe('John');
    });

    test('getOrSet sets and returns default for missing key', function () {
        $array = [];
        $result = ArrayCollection::getOrSet($array, 'status', 'active');
        expect($result)->toBe('active');
        expect($array['status'])->toBe('active');
    });

    test('getOrSet works with callable defaults', function () {
        $array = [];
        $callCount = 0;
        $result = ArrayCollection::getOrSet($array, 'config', function () use (&$callCount) {
            $callCount++;

            return ['timeout' => 30];
        });
        expect($result)->toBe(['timeout' => 30]);
        expect($array['config'])->toBe(['timeout' => 30]);
        expect($callCount)->toBe(1);
    });

    test('getOrSet does not invoke callable for existing keys', function () {
        $array = ['value' => 'existing'];
        $callCount = 0;
        $result = ArrayCollection::getOrSet($array, 'value', function () use (&$callCount) {
            $callCount++;

            return 'new';
        });
        expect($result)->toBe('existing');
        expect($callCount)->toBe(0);
    });

    test('getOrSet supports dot notation for nested keys', function () {
        $array = ['app' => ['name' => 'MyApp']];
        $result = ArrayCollection::getOrSet($array, 'app.timeout', 60);
        expect($result)->toBe(60);
        expect($array['app']['timeout'])->toBe(60);
        expect($array['app']['name'])->toBe('MyApp');
    });

    test('getOrSet creates nested structure with dot notation', function () {
        $array = [];
        $result = ArrayCollection::getOrSet($array, 'cache.redis.host', 'localhost');
        expect($result)->toBe('localhost');
        expect($array['cache']['redis']['host'])->toBe('localhost');
    });

    test('getOrSet returns existing nested value', function () {
        $array = ['user' => ['profile' => ['email' => 'test@example.com']]];
        $result = ArrayCollection::getOrSet($array, 'user.profile.email', 'default@example.com');
        expect($result)->toBe('test@example.com');
        expect($array['user']['profile']['email'])->toBe('test@example.com');
    });

    test('has checks existence using dot notation', function () {
        $array = ['a' => ['b' => 'c']];
        expect(ArrayCollection::has($array, 'a.b'))->toBeTrue();
        expect(ArrayCollection::has($array, 'a.x'))->toBeFalse();
    });

    test('forget removes value using dot notation', function () {
        $array = ['a' => ['b' => 'c', 'd' => 'e']];
        $result = ArrayCollection::forget($array, 'a.b');
        expect(isset($result['a']['b']))->toBeFalse();
        expect($result['a']['d'])->toBe('e');
    });

    test('where filters array by field', function () {
        $array = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
            ['id' => 3, 'name' => 'John'],
        ];
        $result = ArrayCollection::where($array, 'name', 'John');
        expect(count($result))->toBe(2);
        expect(array_values($result)[0]['id'])->toBe(1);
        expect(array_values($result)[1]['id'])->toBe(3);
    });

    test('pluck extracts values', function () {
        $array = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];
        $result = ArrayCollection::pluck($array, 'name');
        expect($result)->toBe(['John', 'Jane']);
    });

    test('build creates associative array', function () {
        $array = [
            ['id' => 1, 'name' => 'John', 'role' => 'admin'],
            ['id' => 2, 'name' => 'Jane', 'role' => 'user'],
        ];
        $result = ArrayCollection::build($array, 'id', 'name');
        expect($result)->toBe([1 => 'John', 2 => 'Jane']);

        $result = ArrayCollection::build($array, 'id');
        expect($result[1]['name'])->toBe('John');
    });

    test('flatten flattens multi-dimensional array', function () {
        $array = ['a', ['b', ['c']]];
        $result = ArrayCollection::flatten($array, false);
        expect($result)->toBe(['a', 'b', 'c']);
    });

    test('mapDeep applies callback recursively', function () {
        $array = ['a' => 1, 'b' => ['c' => 2]];
        $result = ArrayCollection::mapDeep($array, fn ($x) => $x * 2);
        expect($result)->toBe(['a' => 2, 'b' => ['c' => 4]]);
    });

    test('cleanDeep removes empty values recursively', function () {
        $array = ['a' => 1, 'b' => '', 'c' => null, 'd' => ['e' => '', 'f' => 2]];
        $result = ArrayCollection::cleanDeep($array);
        expect($result)->toBe(['a' => 1, 'd' => ['f' => 2]]);
    });

    test('toAssoc converts list to associative array', function () {
        $list = ['a', 'b'];
        $result = ArrayCollection::toAssoc($list);
        expect($result)->toBe(['a' => 'a', 'b' => 'b']);
    });

    test('wrap wraps value in array', function () {
        expect(ArrayCollection::wrap('a'))->toBe(['a']);
        expect(ArrayCollection::wrap(['a']))->toBe(['a']);
        expect(ArrayCollection::wrap(null))->toBe([]);
    });

    test('safeImplode safely implodes mixed values', function () {
        $array = ['a', 1, true, ['b']];
        $result = ArrayCollection::safeImplode($array, ',');
        // true becomes 'true', ['b'] becomes json
        expect($result)->toContain('a,1,true');
        expect($result)->toContain('["b"]');
    });

    test('mean calculates average', function () {
        expect(ArrayCollection::mean([1, 2, 3]))->toBe(2.0);
    });

    test('median calculates median', function () {
        expect(ArrayCollection::median([1, 3, 2]))->toBe(2.0);
        expect(ArrayCollection::median([1, 2, 3, 4]))->toBe(2.5);
    });

    test('mode calculates mode', function () {
        expect(ArrayCollection::mode([1, 1, 2]))->toBe([1]);
        expect(ArrayCollection::mode([1, 1, 2, 2]))->toBe([1, 2]);
    });

    test('variance calculates variance', function () {
        $variance = ArrayCollection::variance([1, 2, 3, 4, 5]);
        expect($variance)->toBeGreaterThan(0);
    });

    test('stdDev calculates standard deviation', function () {
        $stdDev = ArrayCollection::stdDev([1, 2, 3, 4, 5]);
        expect($stdDev)->toBeGreaterThan(0);
    });

    test('whereIn filters by field in collection', function () {
        $array = [
            ['id' => 1, 'status' => 'active'],
            ['id' => 2, 'status' => 'inactive'],
            ['id' => 3, 'status' => 'pending'],
        ];
        $result = ArrayCollection::whereIn($array, 'status', ['active', 'pending']);
        expect(count($result))->toBe(2);
    });

    test('whereNotIn filters by field not in collection', function () {
        $array = [
            ['id' => 1, 'status' => 'active'],
            ['id' => 2, 'status' => 'inactive'],
            ['id' => 3, 'status' => 'pending'],
        ];
        $result = ArrayCollection::whereNotIn($array, 'status', ['inactive']);
        expect(count($result))->toBe(2);
    });

    test('groupByKey groups items by key', function () {
        $array = [
            ['type' => 'fruit', 'name' => 'apple'],
            ['type' => 'fruit', 'name' => 'banana'],
            ['type' => 'vegetable', 'name' => 'carrot'],
        ];
        $result = ArrayCollection::groupByKey($array, 'type');
        expect(count($result['fruit']))->toBe(2);
        expect(count($result['vegetable']))->toBe(1);
    });

    test('uniqueBy removes duplicates by key', function () {
        $array = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
            ['id' => 1, 'name' => 'John Duplicate'],
        ];
        $result = ArrayCollection::uniqueBy($array, 'id');
        expect(count($result))->toBe(2);
    });

    test('unique removes duplicate values', function () {
        $result = ArrayCollection::unique([1, 2, 2, 3, 3, 3]);
        expect($result)->toBe([1, 2, 3]);
    });

    test('sortByField sorts by field', function () {
        $array = [
            ['name' => 'Charlie', 'age' => 30],
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 35],
        ];
        $result = ArrayCollection::sortByField($array, 'age');
        expect($result[0]['name'])->toBe('Alice');
        expect($result[2]['name'])->toBe('Bob');
    });

    test('first returns first element', function () {
        expect(ArrayCollection::first([1, 2, 3]))->toBe(1);
    });

    test('last returns last element', function () {
        expect(ArrayCollection::last([1, 2, 3]))->toBe(3);
    });

    test('firstKey returns first key', function () {
        expect(ArrayCollection::firstKey(['a' => 1, 'b' => 2]))->toBe('a');
    });

    test('lastKey returns last key', function () {
        expect(ArrayCollection::lastKey(['a' => 1, 'b' => 2]))->toBe('b');
    });

    test('max returns maximum value', function () {
        expect(ArrayCollection::max([1, 5, 3]))->toBe(5);
    });

    test('min returns minimum value', function () {
        expect(ArrayCollection::min([1, 5, 3]))->toBe(1);
    });

    test('chunk splits array into chunks', function () {
        $result = ArrayCollection::chunk([1, 2, 3, 4, 5], 2);
        expect(count($result))->toBe(3);
        expect($result[0])->toBe([1, 2]);
    });

    test('take takes specified number of items', function () {
        $result = ArrayCollection::take([1, 2, 3, 4, 5], 3);
        expect(count($result))->toBe(3);
        expect($result)->toBe([1, 2, 3]);
    });

    test('reverse reverses array', function () {
        $result = ArrayCollection::reverse([1, 2, 3]);
        expect(array_values($result))->toBe([3, 2, 1]);
    });

    test('flip exchanges keys and values', function () {
        $result = ArrayCollection::flip(['a' => 1, 'b' => 2]);
        expect($result)->toBe([1 => 'a', 2 => 'b']);
    });

    test('combine combines keys and values', function () {
        $result = ArrayCollection::combine(['a', 'b'], [1, 2]);
        expect($result)->toBe(['a' => 1, 'b' => 2]);
    });

    test('getKeys returns array keys', function () {
        $result = ArrayCollection::getKeys(['a' => 1, 'b' => 2]);
        expect($result)->toBe(['a', 'b']);
    });

    test('values returns array values', function () {
        $result = ArrayCollection::values(['a' => 1, 'b' => 2]);
        expect($result)->toBe([1, 2]);
    });

    test('sum calculates sum', function () {
        expect(ArrayCollection::sum([1, 2, 3]))->toBe(6);
    });

    test('count counts elements', function () {
        expect(ArrayCollection::count([1, 2, 3]))->toBe(3);
    });

    test('isEmpty checks if empty', function () {
        expect(ArrayCollection::isEmpty([]))->toBeTrue();
        expect(ArrayCollection::isEmpty([1]))->toBeFalse();
    });

    test('isAssoc checks if associative', function () {
        expect(ArrayCollection::isAssoc(['a' => 1]))->toBeTrue();
        expect(ArrayCollection::isAssoc([1, 2, 3]))->toBeFalse();
    });

    test('each iterates over items', function () {
        $sum = 0;
        ArrayCollection::each([1, 2, 3], function ($value) use (&$sum) {
            $sum += $value;
        });
        expect($sum)->toBe(6);
    });

    test('reduce reduces to single value', function () {
        $result = ArrayCollection::reduce([1, 2, 3], fn ($carry, $item) => $carry + $item, 0);
        expect($result)->toBe(6);
    });

    test('avg calculates average', function () {
        expect(ArrayCollection::avg([1, 2, 3]))->toBe(2.0);
    });

    test('hasAll checks all keys exist', function () {
        $array = ['a' => 1, 'b' => ['c' => 2]];
        expect(ArrayCollection::hasAll($array, ['a', 'b.c']))->toBeTrue();
        expect(ArrayCollection::hasAll($array, ['a', 'x']))->toBeFalse();
    });

    test('exists checks key exists even if null', function () {
        $array = ['a' => null];
        expect(ArrayCollection::exists($array, 'a'))->toBeTrue();
        expect(ArrayCollection::exists($array, 'b'))->toBeFalse();
    });

    test('only returns only specified keys', function () {
        $result = ArrayCollection::only(['a' => 1, 'b' => 2, 'c' => 3], ['a', 'c']);
        expect($result)->toBe(['a' => 1, 'c' => 3]);
    });

    test('exclude excludes specified keys', function () {
        $result = ArrayCollection::exclude(['a' => 1, 'b' => 2, 'c' => 3], ['b']);
        expect($result)->toBe(['a' => 1, 'c' => 3]);
    });

    test('push adds items to end', function () {
        $result = ArrayCollection::push([1, 2], 3);
        expect($result)->toBe([1, 2, 3]);
    });

    test('prepend adds items to beginning', function () {
        $result = ArrayCollection::prepend(['a' => 2, 'b' => 3], ['c' => 1]);
        expect($result['c'])->toBe(1);
        expect($result['a'])->toBe(2);
    });

    test('remove removes item by key', function () {
        $result = ArrayCollection::remove(['a' => 1, 'b' => 2], 'a');
        expect(isset($result['a']))->toBeFalse();
        expect($result['b'])->toBe(2);
    });

    test('map applies callback recursively', function () {
        $result = ArrayCollection::map(['a' => 1, 'b' => ['c' => 2]], fn ($x) => $x * 2);
        expect($result['a'])->toBe(2);
        expect($result['b']['c'])->toBe(4);
    });

    test('clean filters array with callback', function () {
        $result = ArrayCollection::clean([1, 2, 3, 4, 5], fn ($x) => $x > 2);
        expect(count($result))->toBe(3);
    });

    test('mapWithKeys builds associative array from callback', function () {
        $result = ArrayCollection::mapWithKeys([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ], fn ($item) => [$item['id'] => $item['name']]);
        expect($result)->toBe([1 => 'Alice', 2 => 'Bob']);
    });

    test('indexByCompoundKey creates compound key index', function () {
        $array = [
            ['category' => 'fruit', 'type' => 'apple', 'price' => 1.5],
            ['category' => 'fruit', 'type' => 'banana', 'price' => 0.5],
        ];
        $result = ArrayCollection::indexByCompoundKey($array, ['category', 'type']);
        expect(isset($result['fruit-apple']))->toBeTrue();
        expect($result['fruit-apple']['price'])->toBe(1.5);
    });

    test('orderKeys sorts by key order', function () {
        $array = ['c' => 3, 'a' => 1, 'b' => 2];
        $result = ArrayCollection::orderKeys($array, ['a', 'b', 'c']);
        expect(array_keys($result))->toBe(['a', 'b', 'c']);
    });

    test('replaceKeys replaces array keys', function () {
        $array = [['old_name' => 'John', 'age' => 30]];
        $result = ArrayCollection::replaceKeys($array, ['old_name' => 'new_name']);
        expect(isset($result[0]['new_name']))->toBeTrue();
        expect($result[0]['new_name'])->toBe('John');
    });

    test('replaceValues replaces values based on map', function () {
        $array = ['status' => 'active', 'role' => 'admin'];
        $result = ArrayCollection::replaceValues($array, ['status' => 'enabled', 'role' => 'administrator']);
        expect($result['status'])->toBe('enabled');
        expect($result['role'])->toBe('administrator');
    });

    test('partition divides array by callback', function () {
        [$even, $odd] = ArrayCollection::partition([1, 2, 3, 4, 5], fn ($x) => $x % 2 === 0);
        expect(count($even))->toBe(2);
        expect(count($odd))->toBe(3);
    });

    test('contains checks if value exists', function () {
        expect(ArrayCollection::contains([1, 2, 3], 2))->toBeTrue();
        expect(ArrayCollection::contains([1, 2, 3], 5))->toBeFalse();
    });

    test('shift removes and returns first element', function () {
        $result = ArrayCollection::shift([1, 2, 3]);
        expect($result)->toBe(1);
    });

    test('pop removes and returns last element', function () {
        $result = ArrayCollection::pop([1, 2, 3]);
        expect($result)->toBe(3);
    });

    test('shuffle randomizes array order', function () {
        $original = [1, 2, 3, 4, 5];
        $result = ArrayCollection::shuffle($original);
        expect(count($result))->toBe(5);
        // Can't test exact order due to randomness, but can verify all elements present
        expect(in_array(1, $result))->toBeTrue();
    });

    test('random returns random elements', function () {
        $result = ArrayCollection::random([1, 2, 3, 4, 5], 2);
        expect(count($result))->toBe(2);
    });

    test('limit takes first n elements', function () {
        $result = ArrayCollection::limit([1, 2, 3, 4, 5], 3);
        expect($result)->toBe([1, 2, 3]);
    });

    test('isArrayOfArrays checks if array contains arrays', function () {
        expect(ArrayCollection::isArrayOfArrays([[1], [2]]))->toBeTrue();
        expect(ArrayCollection::isArrayOfArrays([1, 2, 3]))->toBeFalse();
    });

    test('isMultiDimensional checks for nested arrays', function () {
        expect(ArrayCollection::isMultiDimensional(['a' => ['b' => 1]]))->toBeTrue();
        expect(ArrayCollection::isMultiDimensional(['a' => 1]))->toBeFalse();
    });

    test('isEqual checks array equality', function () {
        expect(ArrayCollection::isEqual([1, 2, 3], [1, 2, 3]))->toBeTrue();
        expect(ArrayCollection::isEqual([1, 2, 3], [3, 2, 1]))->toBeTrue();
        expect(ArrayCollection::isEqual([1, 2, 3], [1, 2, 4]))->toBeFalse();
    });

    test('prependKeyed adds element to beginning with key', function () {
        $result = ArrayCollection::prependKeyed(['b' => 2], 'a', 1);
        expect(array_keys($result))->toBe(['a', 'b']);
        expect($result['a'])->toBe(1);
    });

    test('attach merges arrays', function () {
        $result = ArrayCollection::attach(['a' => 1, 'b' => 2], ['c' => 3, 'd' => 4]);
        expect($result)->toBe(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
    });

    test('rebase reindexes array from 0', function () {
        $array = [5 => 'a', 10 => 'b', 15 => 'c'];
        $result = ArrayCollection::rebase($array);
        expect(array_keys($result))->toBe([0, 1, 2]);
    });

    test('zip zips two arrays', function () {
        $result = ArrayCollection::zip([1, 2], ['a', 'b']);
        expect($result)->toBe([[1, 'a'], [2, 'b']]);
    });

    test('toComment converts array to comment string', function () {
        $result = ArrayCollection::toComment(['line1', 'line2']);
        expect($result)->toContain('line1');
        expect($result)->toContain('line2');
    });
});
