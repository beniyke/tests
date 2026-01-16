<?php

declare(strict_types=1);

use App\Models\User;
use Support\Enums\TicketPriority;
use Support\Enums\TicketStatus;
use Support\Models\Ticket;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Support'], true);
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

describe('Ticket Model', function () {

    test('creates ticket with required fields', function () {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret',
            'gender' => 'male',
            'refid' => 'USR123',
        ]);

        $ticket = Ticket::create([
            'refid' => 'TEST123',
            'user_id' => $user->id,
            'subject' => 'Test Subject',
            'description' => 'Test Description',
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::MEDIUM,
        ]);

        expect($ticket)->toBeInstanceOf(Ticket::class)
            ->and($ticket->refid)->toBe('TEST123')
            ->and($ticket->subject)->toBe('Test Subject')
            ->and($ticket->status)->toBe(TicketStatus::OPEN)
            ->and($ticket->priority)->toBe(TicketPriority::MEDIUM);
    });

    test('isOpen returns true for open statuses', function (TicketStatus $status, bool $expected) {
        $ticket = new Ticket();
        $ticket->status = $status;

        expect($ticket->isOpen())->toBe($expected);
    })->with([
        'open' => [TicketStatus::OPEN, true],
        'pending' => [TicketStatus::PENDING, true],
        'in_progress' => [TicketStatus::IN_PROGRESS, true],
        'resolved' => [TicketStatus::RESOLVED, false],
        'closed' => [TicketStatus::CLOSED, false],
    ]);

    test('isResolved returns correct value', function () {
        $ticket = new Ticket();

        $ticket->status = TicketStatus::RESOLVED;
        expect($ticket->isResolved())->toBeTrue();

        $ticket->status = TicketStatus::OPEN;
        expect($ticket->isResolved())->toBeFalse();
    });

    test('isClosed returns correct value', function () {
        $ticket = new Ticket();

        $ticket->status = TicketStatus::CLOSED;
        expect($ticket->isClosed())->toBeTrue();

        $ticket->status = TicketStatus::OPEN;
        expect($ticket->isClosed())->toBeFalse();
    });
});

describe('Ticket Status Enum', function () {

    test('has all expected cases', function () {
        $cases = TicketStatus::cases();

        expect($cases)->toHaveCount(5)
            ->and(array_column($cases, 'value'))->toContain(
                'open',
                'pending',
                'in_progress',
                'resolved',
                'closed'
            );
    });
});

describe('Ticket Priority Enum', function () {

    test('has all expected cases', function () {
        $cases = TicketPriority::cases();

        expect($cases)->toHaveCount(4)
            ->and(array_column($cases, 'value'))->toContain(
                'low',
                'medium',
                'high',
                'urgent'
            );
    });
});
