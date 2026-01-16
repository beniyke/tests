<?php

declare(strict_types=1);

use Core\Ioc\Container;

$originalContainer = null;

beforeAll(function () use (&$originalContainer) {
    // Save original container instance
    $reflection = new ReflectionClass(Container::class);
    $property = $reflection->getProperty('instance');
    $property->setAccessible(true);
    $originalContainer = $property->getValue();
});

afterAll(function () use (&$originalContainer) {
    // Restore original container instance
    if ($originalContainer) {
        $reflection = new ReflectionClass(Container::class);
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, $originalContainer);
    }
});

beforeEach(function () {
    // Reset container instance before each test to ensure isolation
    $reflection = new ReflectionClass(Container::class);
    $property = $reflection->getProperty('instance');
    $property->setAccessible(true);
    $property->setValue(null, null);

    $this->container = Container::getInstance();
});

test('it is a singleton', function () {
    $container1 = Container::getInstance();
    $container2 = Container::getInstance();

    expect($container1)->toBe($container2);
});

test('it can bind and resolve a class', function () {
    $this->container->bind('foo', function () {
        return 'bar';
    });

    expect($this->container->get('foo'))->toBe('bar');
});

test('it can bind a singleton', function () {
    $this->container->singleton('random', function () {
        return rand(1, 1000);
    });

    $first = $this->container->get('random');
    $second = $this->container->get('random');

    expect($first)->toBe($second);
});

test('it can bind an instance', function () {
    $instance = new stdClass();
    $this->container->instance('my_instance', $instance);

    expect($this->container->get('my_instance'))->toBe($instance);
});

test('it resolves class dependencies automatically', function () {
    class TestDependency
    {
    }
    class TestDependent
    {
        public function __construct(public TestDependency $dependency)
        {
        }
    }

    $instance = $this->container->get(TestDependent::class);

    expect($instance)->toBeInstanceOf(TestDependent::class)
        ->and($instance->dependency)->toBeInstanceOf(TestDependency::class);
});

test('it resolves nested dependencies', function () {
    class TestDeepDependency
    {
    }
    class TestMiddleDependency
    {
        public function __construct(public TestDeepDependency $deep)
        {
        }
    }
    class TestTopLevel
    {
        public function __construct(public TestMiddleDependency $middle)
        {
        }
    }

    $instance = $this->container->get(TestTopLevel::class);

    expect($instance->middle)->toBeInstanceOf(TestMiddleDependency::class)
        ->and($instance->middle->deep)->toBeInstanceOf(TestDeepDependency::class);
});

test('it supports contextual binding', function () {
    interface TestContextInterface
    {
    }
    class TestConcreteA implements TestContextInterface
    {
    }
    class TestConcreteB implements TestContextInterface
    {
    }

    class TestClassA
    {
        public function __construct(public TestContextInterface $dependency)
        {
        }
    }
    class TestClassB
    {
        public function __construct(public TestContextInterface $dependency)
        {
        }
    }

    $this->container->when(TestClassA::class)
        ->needs(TestContextInterface::class)
        ->give(TestConcreteA::class);

    $this->container->when(TestClassB::class)
        ->needs(TestContextInterface::class)
        ->give(TestConcreteB::class);

    $a = $this->container->get(TestClassA::class);
    $b = $this->container->get(TestClassB::class);

    expect($a->dependency)->toBeInstanceOf(TestConcreteA::class)
        ->and($b->dependency)->toBeInstanceOf(TestConcreteB::class);
});

test('it can tag and resolve tagged services', function () {
    $this->container->bind('service1', fn () => 'one');
    $this->container->bind('service2', fn () => 'two');

    $this->container->tag(['service1', 'service2'], 'my_tag');

    $results = $this->container->tagged('my_tag');

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(2)
        ->and($results[0])->toBe('one')
        ->and($results[1])->toBe('two');
});

test('it can call a method with dependency injection', function () {
    class MethodDependency
    {
    }
    class MethodCaller
    {
        public function action(MethodDependency $dep, $param)
        {
            return [$dep, $param];
        }
    }

    $caller = new MethodCaller();
    $result = $this->container->call([$caller, 'action'], ['param' => 'value']);

    expect($result[0])->toBeInstanceOf(MethodDependency::class)
        ->and($result[1])->toBe('value');
});

test('it throws exception for unresolvable dependency', function () {
    class Unresolvable
    {
        public function __construct(public $unknown)
        {
        }
    }

    expect(fn () => $this->container->get(Unresolvable::class))->toThrow(Exception::class);
});

test('it resolves default values', function () {
    class DefaultValue
    {
        public function __construct(public $value = 'default')
        {
        }
    }

    $instance = $this->container->get(DefaultValue::class);
    expect($instance->value)->toBe('default');
});

test('it can make an instance with parameters', function () {
    class MakeWithParams
    {
        public function __construct(public $a, public $b)
        {
        }
    }

    $instance = $this->container->make(MakeWithParams::class, ['a' => 1, 'b' => 2]);

    expect($instance->a)->toBe(1)
        ->and($instance->b)->toBe(2);
});
