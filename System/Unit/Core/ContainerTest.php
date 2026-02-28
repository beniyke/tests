<?php

declare(strict_types=1);

namespace Tests\System\Unit\Core;

use Core\Ioc\Container;
use Core\Ioc\ContainerInterface;
use Exception;
use ReflectionClass;
use stdClass;
use Tests\System\Support\Core\Container\DefaultValue;
use Tests\System\Support\Core\Container\MakeWithParams;
use Tests\System\Support\Core\Container\MethodCaller;
use Tests\System\Support\Core\Container\MethodDependency;
use Tests\System\Support\Core\Container\OptionalDependent;
use Tests\System\Support\Core\Container\RequiredDependent;
use Tests\System\Support\Core\Container\TestClassA;
use Tests\System\Support\Core\Container\TestClassB;
use Tests\System\Support\Core\Container\TestConcreteA;
use Tests\System\Support\Core\Container\TestConcreteB;
use Tests\System\Support\Core\Container\TestContextInterface;
use Tests\System\Support\Core\Container\TestDeepDependency;
use Tests\System\Support\Core\Container\TestDependency;
use Tests\System\Support\Core\Container\TestDependent;
use Tests\System\Support\Core\Container\TestMiddleDependency;
use Tests\System\Support\Core\Container\TestTopLevel;
use Tests\System\Support\Core\Container\Unresolvable;

describe('Container', function () {
    beforeEach(function () {
        // Use reflection to create a new instance of Container since constructor is private
        $reflection = new ReflectionClass(Container::class);
        $this->container = $reflection->newInstanceWithoutConstructor();

        // Call the constructor if it's defined (even if private)
        $constructor = $reflection->getConstructor();
        if ($constructor) {
            $constructor->setAccessible(true);
            $constructor->invoke($this->container);
        }

        // Bind ContainerInterface to the instance itself for the tests
        $this->container->singleton(ContainerInterface::class, fn () => $this->container);
    });

    describe('Singleton Pattern', function () {
        test('it is a singleton', function () {
            // Note: This tests the static singleton pattern of the class, not our local instance
            $container1 = Container::getInstance();
            $container2 = Container::getInstance();

            expect($container1)->toBe($container2);
        });
    });

    describe('Bindings', function () {
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
    });

    describe('Automatic Resolution', function () {
        test('it resolves class dependencies automatically', function () {
            $instance = $this->container->get(TestDependent::class);

            expect($instance)->toBeInstanceOf(TestDependent::class)
                ->and($instance->dependency)->toBeInstanceOf(TestDependency::class);
        });

        test('it resolves nested dependencies', function () {
            $instance = $this->container->get(TestTopLevel::class);

            expect($instance->middle)->toBeInstanceOf(TestMiddleDependency::class)
                ->and($instance->middle->deep)->toBeInstanceOf(TestDeepDependency::class);
        });

        test('it resolves default values', function () {
            $instance = $this->container->get(DefaultValue::class);
            expect($instance->value)->toBe('default');
        });

        test('it resolves optional unbound interfaces to null', function () {
            $instance = $this->container->get(OptionalDependent::class);
            expect($instance->opt)->toBeNull();
        })->group('di-optional');
    });

    describe('Contextual Binding', function () {
        test('it supports contextual binding', function () {
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
    });

    describe('Tagging', function () {
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
    });

    describe('Method Injection', function () {
        test('it can call a method with dependency injection', function () {
            $caller = new MethodCaller();
            $result = $this->container->call([$caller, 'action'], ['param' => 'value']);

            expect($result[0])->toBeInstanceOf(MethodDependency::class)
                ->and($result[1])->toBe('value');
        });
    });

    describe('Edge Cases & Errors', function () {
        test('it throws exception for unresolvable dependency', function () {
            expect(fn () => $this->container->get(Unresolvable::class))->toThrow(Exception::class);
        });

        test('it throws exception for required unbound interfaces', function () {
            expect(fn () => $this->container->get(RequiredDependent::class))->toThrow(Exception::class);
        })->group('di-optional');

        test('it can make an instance with parameters', function () {
            $instance = $this->container->make(MakeWithParams::class, ['a' => 1, 'b' => 2]);

            expect($instance->a)->toBe(1)
                ->and($instance->b)->toBe(2);
        });
    });
});
