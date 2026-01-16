<?php

declare(strict_types=1);

namespace Tests\Packages\Watcher\Unit;

use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Response;
use Mockery;
use Watcher\Alerts\Channels\WebhookChannel;

test('WebhookChannel sends post request', function () {
    $curl = Mockery::mock(Curl::class);
    $curl->shouldReceive('post')->once()->andReturnSelf();
    $curl->shouldReceive('send')->once()->andReturn(Mockery::mock(Response::class));

    $channel = Mockery::mock(WebhookChannel::class, ['https://example.com/webhook'])->makePartial();
    $channel->shouldAllowMockingProtectedMethods();
    $channel->shouldReceive('newCurl')->once()->andReturn($curl);

    $channel->send('error_rate', ['rate' => 10.0]);
});
