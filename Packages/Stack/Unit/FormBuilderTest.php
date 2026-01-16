<?php

declare(strict_types=1);

namespace Tests\Packages\Stack\Unit;

use Mockery;
use RuntimeException;
use Stack\Models\Field;
use Stack\Models\Form;
use Stack\Services\Builders\FormBuilder;
use Stack\Services\StackManagerService;
use Testing\Concerns\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->bootPackage('Stack', null, true);
    $this->fakeAudit();
});

describe('FormBuilder', function () {
    test('can set basic form properties', function () {
        $manager = Mockery::mock(StackManagerService::class);
        $builder = new FormBuilder($manager);

        $builder->title('Contact Us')
            ->description('Get in touch with us')
            ->active();

        $manager->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['title'] === 'Contact Us' &&
                    $data['description'] === 'Get in touch with us' &&
                    $data['status'] === 'active';
            }))
            ->andReturn($form = Mockery::mock(Form::class));

        $form->shouldReceive('castAttributeOnSet')->andReturnArg(1);
        $form->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $form->id = 1;

        $builder->create();
    });

    test('generates a slug from the title if not provided', function () {
        $manager = Mockery::mock(StackManagerService::class);
        $builder = new FormBuilder($manager);

        $builder->title('Product Feedback');

        $manager->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['slug'] === 'product-feedback';
            }))
            ->andReturn($form = Mockery::mock(Form::class));

        $form->shouldReceive('castAttributeOnSet')->andReturnArg(1);
        $form->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $form->id = 1;

        $builder->create();
    });

    test('can build a form with fields', function () {
        // For this test, we use the real service to verify field creation
        $manager = resolve(StackManagerService::class);
        $builder = new FormBuilder($manager);

        $builder->title('Survey')
            ->withField('email', 'Email Address')
            ->type('email')
            ->required()
            ->add();

        $form = $builder->create();

        expect($form->id)->not->toBeNull();
        expect(Field::where('stack_form_id', $form->id)->count())->toBe(1);
    });

    test('throws exception if title is missing', function () {
        $manager = Mockery::mock(StackManagerService::class);
        $builder = new FormBuilder($manager);

        expect(fn () => $builder->create())->toThrow(RuntimeException::class, 'Form title is required');
    });
});
