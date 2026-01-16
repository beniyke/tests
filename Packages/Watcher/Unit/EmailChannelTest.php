<?php

declare(strict_types=1);

namespace Tests\Packages\Watcher\Unit;

use Watcher\Alerts\Channels\EmailChannel;
use Watcher\Alerts\Mail\WatcherAlert;

test('EmailChannel sends email', function () {
    $mail = $this->fakeMail();

    $channel = new EmailChannel(['admin@example.com']);
    $channel->send('error_rate', ['error_rate' => 10.0]);

    $mail->assertSent(WatcherAlert::class, function ($alert) {
        $recipients = $alert->getRecipients();

        return $recipients['to'] === ['admin@example.com'];
    });
});
