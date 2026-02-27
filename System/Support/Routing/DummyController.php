<?php

declare(strict_types=1);

namespace Tests\System\Support\Routing;

class DummyController
{
    public function index(): string
    {
        return 'index';
    }

    public function create(): string
    {
        return 'create';
    }

    public function store(): string
    {
        return 'store';
    }

    public function show($id): string
    {
        return 'show ' . $id;
    }

    public function edit($id): string
    {
        return 'edit ' . $id;
    }

    public function update($id): string
    {
        return 'update ' . $id;
    }

    public function destroy($id): string
    {
        return 'confirm delete ' . $id;
    }
}
