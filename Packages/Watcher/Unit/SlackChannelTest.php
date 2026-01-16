<?php

declare(strict_types=1);

namespace Tests\Packages\Watcher\Unit;

use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Response;
use Mockery;
use Watcher\Alerts\Channels\SlackChannel;

test('SlackChannel sends webhook', function () {
    $curl = Mockery::mock(Curl::class);
    $curl->shouldReceive('post')->once()->andReturn($curl);
    $curl->shouldReceive('asJson')->once()->andReturn($curl);
    $curl->shouldReceive('send')->once()->andReturn(Mockery::mock(Response::class));

    $channel = Mockery::mock(SlackChannel::class, ['https://hooks.slack.com/services/xxx'])->makePartial();
    $channel->shouldAllowMockingProtectedMethods();
    $channel->shouldReceive('newCurl')->once()->andReturn($curl);

    $channel->send('error_rate', ['error_rate' => 10.0, 'threshold' => 5.0]);
});
