<?php

declare(strict_types=1);

describe('Pulse Architecture', function () {
    arch('pulse models should only use allowed dependencies')
        ->expect('Pulse\\Models')
        ->toOnlyUse([
            'Database\\BaseModel',
            'Database\\Relations',
            'Database\\Collections\\ModelCollection',
            'App\\Models\\User',
            'Pulse\\Models',
            'Helpers\\DateTimeHelper',
        ]);
});
