<?php

declare(strict_types=1);

use Guide\Guide;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    $this->refreshDatabase();
    DatabaseTestHelper::runPackageMigrations('Guide');
    DatabaseTestHelper::runPackageMigrations('Audit');
});

test('can create category fluently', function () {
    $category = Guide::category()
        ->name('Getting Started')
        ->description('Initial guides')
        ->create();

    $this->assertDatabaseHas('guide_category', [
        'name' => 'Getting Started',
        'slug' => 'getting-started'
    ]);

    expect($category->description)->toBe('Initial guides');
    expect($category->refid)->toStartWith('cat_');

    $found = Guide::findCategoryByRefId($category->refid);
    expect($found->id)->toEqual($category->id);
});

test('can create article fluently', function () {
    $category = Guide::category()->name('Support')->create();

    $article = Guide::article()
        ->title('Contact Us')
        ->content('Email us at support@example.com')
        ->category($category)
        ->status('published')
        ->create();

    $this->assertDatabaseHas('guide_article', [
        'title' => 'Contact Us',
        'guide_category_id' => $category->id,
        'status' => 'published'
    ]);

    expect($article->refid)->toStartWith('art_');

    $found = Guide::findArticleByRefId($article->refid);
    expect($found->id)->toEqual($article->id);
});

test('search logic and analytics', function () {
    Guide::article()
        ->title('How to Pay')
        ->content('Use your credit card.')
        ->status('published')
        ->create();

    $results = Guide::search('pay', [], ['ip' => '1.2.3.4', 'source' => 'web']);

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('How to Pay');

    $this->assertDatabaseHas('guide_search_log', [
        'query' => 'pay',
        'results_count' => 1,
        'ip_address' => '1.2.3.4'
    ]);
});

test('feedback system', function () {
    $article = Guide::article()
        ->title('Helpful Doc')
        ->content('Content')
        ->create();

    Guide::analytics()->submitFeedback($article, 5, 'Great!', null, ['ip' => '127.0.0.1']);

    $this->assertDatabaseHas('guide_feedback', [
        'guide_article_id' => $article->id,
        'rating' => 5,
        'comment' => 'Great!',
        'ip_address' => '127.0.0.1'
    ]);
});

test('view tracking', function () {
    $article = Guide::article()
        ->title('Popular Doc')
        ->content('Content')
        ->create();

    Guide::analytics()->recordView($article);
    Guide::analytics()->recordView($article);

    expect($article->fresh()->view_count)->toBe(2);
});
