<?php

declare(strict_types=1);

use App\Models\User;
use Database\DB;
use Support\Enums\TicketPriority;
use Support\Enums\TicketStatus;
use Support\Models\Ticket;
use Tests\System\Support\Helpers\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Support'], true);
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

test('user table exists and ticket can be created', function () {
    $tables = DB::connection()->getTables();
    echo "Tables: " . implode(', ', $tables) . "\n";

    $user = User::create([
        'name' => 'Test',
        'email' => 'test' . rand(1, 9999) . '@test.com',
        'password' => 'secret',
        'gender' => 'male',
        'refid' => 'USR' . rand(1, 9999)
    ]);

    expect($user->id)->toBeGreaterThan(0);

    $ticket = Ticket::create([
        'refid' => 'T' . rand(1, 9999),
        'user_id' => $user->id,
        'subject' => 'Sub',
        'description' => 'Desc',
        'status' => TicketStatus::OPEN,
        'priority' => TicketPriority::MEDIUM
    ]);

    expect($ticket->id)->toBeGreaterThan(0);
});
