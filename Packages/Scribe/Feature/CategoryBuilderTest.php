<?php

declare(strict_types=1);

namespace Tests\Packages\Scribe\Feature;

use Scribe\Models\Category;
use Scribe\Scribe;

beforeEach(function () {
    $this->refreshDatabase();
    $this->bootPackage('Audit', runMigrations: true);
    $this->bootPackage('Scribe', runMigrations: true);
});

test('scribe can create category via builder', function () {
    $category = Scribe::category()
        ->name('Technology')
        ->description('Tech related posts')
        ->create();

    expect($category)->toBeInstanceOf(Category::class);
    expect($category->name)->toBe('Technology');
    expect($category->slug)->toBe('technology');
    expect($category->description)->toBe('Tech related posts');
    expect($category->refid)->toStartWith('cat_');

    $found = Scribe::findCategoryByRefId($category->refid);
    expect($found->id)->toEqual($category->id);
    expect($found->name)->toBe('Technology');
});

test('scribe can create nested categories', function () {
    $parent = Scribe::category()
        ->name('Parent')
        ->create();

    $child = Scribe::category()
        ->name('Child')
        ->parent($parent)
        ->create();

    expect($child->parent_id)->toEqual($parent->id);
    expect($child->parent->name)->toBe('Parent');
});
