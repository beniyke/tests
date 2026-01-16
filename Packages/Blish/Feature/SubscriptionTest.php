<?php

declare(strict_types=1);

namespace Tests\Packages\Blish\Feature;

use Blish\Blish;
use Blish\Models\Subscriber;
use Testing\Concerns\RefreshDatabase;
use Tests\PackageTestCase;

class SubscriptionTest extends PackageTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootPackage('Blish', \Blish\Providers\BlishServiceProvider::class, true);
    }

    public function test_it_can_subscribe_a_user(): void
    {
        $subscriber = Blish::subscribe('test@example.com', ['name' => 'Test User']);

        $this->assertInstanceOf(Subscriber::class, $subscriber);
        $this->assertEquals('test@example.com', $subscriber->email);
        $this->assertEquals('Test User', $subscriber->name);
        $this->assertEquals('pending', $subscriber->status);
        $this->assertNotNull($subscriber->refid);
    }

    public function test_it_can_find_a_subscriber(): void
    {
        Blish::subscribe('find@example.com');

        $subscriber = Blish::find('find@example.com');

        $this->assertNotNull($subscriber);
        $this->assertEquals('find@example.com', $subscriber->email);
    }

    public function test_it_can_unsubscribe_a_user(): void
    {
        Blish::subscribe('unsub@example.com', ['status' => 'active']);

        Blish::unsubscribe('unsub@example.com');

        $subscriber = Blish::find('unsub@example.com');
        $this->assertEquals('unsubscribed', $subscriber->status);
        $this->assertNotNull($subscriber->unsubscribed_at);
    }
}
