<?php

declare(strict_types=1);

namespace Tests\Packages\Support\Feature;

use Helpers\Data\Data;
use Mail\Mail;
use Support\Notifications\TicketRepliedNotification;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Support'], true);
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

describe('Support Notifications', function () {

    test('TicketRepliedNotification has correct data', function () {
        $mail = $this->fakeMail();

        $data = Data::make([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Printer Issue',
            'refid' => 'sup_printer_123',
            'reply_message' => 'We fixed your printer.',
            'ticket_url' => 'support/tickets/sup_printer_123'
        ]);

        $notification = new TicketRepliedNotification($data);
        Mail::send($notification);

        $mail->assertSent(TicketRepliedNotification::class, function ($m) {
            return $m->getSubject() === 'Reply to your ticket: Printer Issue'
                && $m->getRecipients()['to']['john@example.com'] === 'John Doe';
        });
    });
});
