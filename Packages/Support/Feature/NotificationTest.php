<?php

declare(strict_types=1);

namespace Tests\Packages\Support\Feature;

use Helpers\Data\Data;
use Mail\Mail;
use Support\Notifications\TicketRepliedNotification;
use Support\Notifications\UrgentTicketAlert;
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

    test('UrgentTicketAlert has correct data', function () {
        $mail = $this->fakeMail();

        $data = Data::make([
            'recipient_email' => 'agent@example.com',
            'recipient_name' => 'Support Agent',
            'subject' => 'Server Down',
            'refid' => 'sup_server_911',
            'customer_name' => 'Jane Smith',
            'description' => 'The server is completely unresponsive.',
            'manage_url' => 'admin/support/tickets/sup_server_911'
        ]);

        $notification = new UrgentTicketAlert($data);
        Mail::send($notification);

        $mail->assertSent(UrgentTicketAlert::class, function ($m) {
            return $m->getSubject() === 'URGENT: Escalated Ticket - Server Down'
                && $m->getRecipients()['to']['agent@example.com'] === 'Support Agent';
        });
    });
});
