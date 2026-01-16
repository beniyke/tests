<?php

declare(strict_types=1);

use Tests\System\Fixtures\Models\PolyImage;
use Tests\System\Fixtures\Models\PolyPost;
use Tests\System\Fixtures\Models\PolyUser;

test('morph one relationship', function () {
    $post = PolyPost::create(['title' => 'Test Post']);
    $post->image()->create(['url' => 'post-image.jpg']);

    $user = PolyUser::create(['name' => 'Test User']);
    $user->image()->create(['url' => 'user-image.jpg']);

    expect($post->image->url)->toBe('post-image.jpg');
    expect($user->image->url)->toBe('user-image.jpg');

    expect($post->image->imageable)->toBeInstanceOf(PolyPost::class);
    expect($user->image->imageable)->toBeInstanceOf(PolyUser::class);
    expect($post->image->imageable->title)->toBe('Test Post');
});

test('morph many relationship', function () {
    $post = PolyPost::create(['title' => 'Test Post']);
    $post->comments()->create(['body' => 'Comment 1']);
    $post->comments()->create(['body' => 'Comment 2']);

    expect($post->comments)->toHaveCount(2);
    expect($post->comments[0]->body)->toBe('Comment 1');
    expect($post->comments[0]->commentable)->toBeInstanceOf(PolyPost::class);
});

test('polymorphic eager loading', function () {
    $post = PolyPost::create(['title' => 'Test Post']);
    $post->image()->create(['url' => 'post-image.jpg']);

    $user = PolyUser::create(['name' => 'Test User']);
    $user->image()->create(['url' => 'user-image.jpg']);

    $images = PolyImage::with('imageable')->get();

    expect($images)->toHaveCount(2);

    $postImage = $images->filter(fn ($img) => $img->imageable_type === PolyPost::class)->first();
    $userImage = $images->filter(fn ($img) => $img->imageable_type === PolyUser::class)->first();

    expect($postImage->imageable)->toBeInstanceOf(PolyPost::class);
    expect($userImage->imageable)->toBeInstanceOf(PolyUser::class);
    expect($postImage->imageable->title)->toBe('Test Post');
});
