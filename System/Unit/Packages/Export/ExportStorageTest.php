<?php

declare(strict_types=1);

namespace Tests\System\Unit\Packages\Export;

use Core\Ioc\Container;
use Core\Services\ConfigServiceInterface;
use Export\Models\ExportHistory;
use Export\Services\Exporters\CsvExporter;
use Export\Services\Exporters\JsonExporter;
use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Helpers\File\Storage\Storage;
use Mockery;
use Tests\System\Fixtures\Exporters\ArrayExporterStub;

beforeEach(function () {
    $this->tempDir = Paths::storagePath('testing/exports_' . uniqid());
    if (!is_dir($this->tempDir)) {
        mkdir($this->tempDir, 0755, true);
    }

    // Mock Config
    $this->config = Mockery::mock(ConfigServiceInterface::class);
    $this->config->shouldReceive('get')->with('export.path', 'exports')->andReturn('exports');
    $this->config->shouldReceive('get')->with('export.chunk_size', 1000)->andReturn(1000);
    $this->config->shouldReceive('get')->with('export.formats.csv.delimiter', ',')->andReturn(',');
    $this->config->shouldReceive('get')->with('export.formats.csv.enclosure', '"')->andReturn('"');

    // Storage Config
    $this->config->shouldReceive('get')->with('filesystems.default', 'local')->andReturn('local');
    $this->config->shouldReceive('get')->with('filesystems.disks.local')->andReturn([
        'driver' => 'local',
        'root' => $this->tempDir,
    ]);

    // Bind Config to Container for Storage Facade
    Container::getInstance()->instance(ConfigServiceInterface::class, $this->config);
});

afterEach(function () {
    Mockery::close();
    if (is_dir($this->tempDir)) {
        FileSystem::delete($this->tempDir);
    }
});

afterAll(function () {
    $testingDir = Paths::storagePath('testing');
    if (is_dir($testingDir)) {
        FileSystem::delete($testingDir);
    }
});

test('CsvExporter creates file using Storage', function () {
    $exporter = new CsvExporter($this->config);
    $exportable = new ArrayExporterStub();

    $history = new ExportHistory();
    $history->filename = 'test.csv';

    $result = $exporter->export($exportable, $history);

    $expectedPath = 'exports/test.csv';
    expect(Storage::exists($expectedPath))->toBeTrue();
    expect($result['path'])->toBe($expectedPath);

    $content = Storage::get($expectedPath);
    expect($content)->toContain('id,name');
    expect($content)->toContain('1,"Test Item 1"');
});

test('JsonExporter creates file using Storage', function () {
    $exporter = new JsonExporter($this->config);
    $exportable = new ArrayExporterStub();

    $history = new ExportHistory();
    $history->filename = 'test.json';

    $result = $exporter->export($exportable, $history);

    $expectedPath = 'exports/test.json';
    expect(Storage::exists($expectedPath))->toBeTrue();
    expect($result['path'])->toBe($expectedPath);

    $content = Storage::get($expectedPath);
    expect($content)->toContain('"name": "Test Item 1"');
});
