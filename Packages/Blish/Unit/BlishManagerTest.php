<?php

declare(strict_types=1);

namespace Tests\Packages\Blish\Unit;

use Blish\Services\BlishManagerService;
use Blish\Services\Builders\CampaignBuilder;
use Blish\Services\Builders\SubscriberBuilder;
use Tests\PackageTestCase;

class BlishManagerTest extends PackageTestCase
{
    protected BlishManagerService $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new BlishManagerService();
    }

    public function test_it_returns_subscriber_builder(): void
    {
        $builder = $this->manager->subscriber();
        $this->assertInstanceOf(SubscriberBuilder::class, $builder);
    }

    public function test_it_returns_campaign_builder(): void
    {
        $builder = $this->manager->campaign();
        $this->assertInstanceOf(CampaignBuilder::class, $builder);
    }
}
