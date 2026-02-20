<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Exporters;

use Export\Contracts\Exportable;

class ArrayExporterStub implements Exportable
{
    public function headers(): array
    {
        return ['id', 'name'];
    }

    public function query(): array
    {
        return [
            ['id' => 1, 'name' => 'Test Item 1'],
            ['id' => 2, 'name' => 'Test Item 2'],
        ];
    }

    public function map($row): array
    {
        return $row;
    }

    public function filename(): string
    {
        return 'test_export';
    }
}
