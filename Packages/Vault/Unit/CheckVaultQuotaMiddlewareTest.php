<?php

declare(strict_types=1);

namespace Tests\Packages\Vault\Unit;

use Helpers\Http\FileHandler;
use Helpers\Http\Request;
use Helpers\Http\Response;
use Mockery;
use Vault\Exceptions\QuotaExceededException;
use Vault\Middleware\CheckVaultQuotaMiddleware;
use Vault\Services\VaultManagerService;

describe('CheckVaultQuotaMiddleware', function () {
    beforeEach(function () {
        $this->vaultManager = Mockery::mock(VaultManagerService::class);
        $this->middleware = new CheckVaultQuotaMiddleware($this->vaultManager);
        $this->request = Mockery::mock(Request::class);
        $this->response = Mockery::mock(Response::class);
        $this->next = fn ($req, $res) => $res;
    });

    afterEach(function () {
        Mockery::close();
    });

    it('passes through when no files uploaded', function () {
        $this->request->shouldReceive('hasFile')
            ->once()
            ->andReturn(false);

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });

    it('passes through when no account context', function () {
        $this->request->shouldReceive('hasFile')
            ->once()
            ->andReturn(true);

        $this->request->shouldReceive('header')
            ->with('X-Account-ID')
            ->andReturn(null);

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });

    it('allows upload when quota available', function () {
        $file = Mockery::mock(FileHandler::class);
        $file->shouldReceive('getSize')->andReturn(104857600); // 100MB

        $this->request->shouldReceive('hasFile')
            ->once()
            ->andReturn(true);

        $this->request->shouldReceive('header')
            ->with('X-Account-ID')
            ->andReturn('account-123');

        $this->request->shouldReceive('file')
            ->once()
            ->andReturn(['document' => $file]);

        $this->vaultManager->shouldReceive('canUpload')
            ->with('account-123', 104857600)
            ->andReturn(true);

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });

    it('rejects upload when quota exceeded', function () {
        $file = Mockery::mock(FileHandler::class);
        $file->shouldReceive('getSize')->andReturn(104857600);

        $this->request->shouldReceive('hasFile')
            ->once()
            ->andReturn(true);

        $this->request->shouldReceive('header')
            ->with('X-Account-ID')
            ->andReturn('account-123');

        $this->request->shouldReceive('file')
            ->once()
            ->andReturn(['document' => $file]);

        $this->vaultManager->shouldReceive('canUpload')
            ->with('account-123', 104857600)
            ->andReturn(false);

        $this->response->shouldReceive('json')
            ->once()
            ->with([
                'error' => 'Storage quota exceeded',
                'message' => 'Insufficient storage space for this upload'
            ], 413)
            ->andReturnSelf();

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });

    it('handles QuotaExceededException gracefully', function () {
        $file = Mockery::mock(FileHandler::class);
        $file->shouldReceive('getSize')->andReturn(104857600);

        $this->request->shouldReceive('hasFile')
            ->once()
            ->andReturn(true);

        $this->request->shouldReceive('header')
            ->with('X-Account-ID')
            ->andReturn('account-123');

        $this->request->shouldReceive('file')
            ->once()
            ->andReturn(['document' => $file]);

        $this->vaultManager->shouldReceive('canUpload')
            ->with('account-123', 104857600)
            ->andThrow(new QuotaExceededException('account-123', 104857600, 0));

        $this->response->shouldReceive('json')
            ->once()
            ->with([
                'error' => 'Storage quota exceeded',
                'message' => "Storage quota exceeded for account 'account-123'. Required: 104857600 bytes, Available: 0 bytes"
            ], 413)
            ->andReturnSelf();

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });

    it('handles multiple files correctly', function () {
        $file1 = Mockery::mock(FileHandler::class);
        $file1->shouldReceive('getSize')->andReturn(52428800); // 50MB

        $file2 = Mockery::mock(FileHandler::class);
        $file2->shouldReceive('getSize')->andReturn(52428800); // 50MB

        $this->request->shouldReceive('hasFile')
            ->once()
            ->andReturn(true);

        $this->request->shouldReceive('header')
            ->with('X-Account-ID')
            ->andReturn('account-123');

        $this->request->shouldReceive('file')
            ->once()
            ->andReturn([
                'document1' => $file1,
                'document2' => $file2
            ]);

        $this->vaultManager->shouldReceive('canUpload')
            ->with('account-123', 104857600) // Total 100MB
            ->andReturn(true);

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });

    it('handles array of files correctly', function () {
        $file1 = Mockery::mock(FileHandler::class);
        $file1->shouldReceive('getSize')->andReturn(52428800);

        $file2 = Mockery::mock(FileHandler::class);
        $file2->shouldReceive('getSize')->andReturn(52428800);

        $this->request->shouldReceive('hasFile')
            ->once()
            ->andReturn(true);

        $this->request->shouldReceive('header')
            ->with('X-Account-ID')
            ->andReturn('account-123');

        $this->request->shouldReceive('file')
            ->once()
            ->andReturn([
                'documents' => [$file1, $file2]
            ]);

        $this->vaultManager->shouldReceive('canUpload')
            ->with('account-123', 104857600)
            ->andReturn(true);

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });
});
