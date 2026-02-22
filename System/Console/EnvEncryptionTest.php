<?php

declare(strict_types=1);

use Cli\Commands\Runners\EnvDecryptCommand;
use Cli\Commands\Runners\EnvEncryptCommand;
use Core\Services\Dotenv;
use Core\Support\Adapters\EnvironmentAdapter;
use Helpers\File\Adapters\FileManipulationAdapter;
use Helpers\File\Adapters\FileMetaAdapter;
use Helpers\File\Adapters\FileReadWriteAdapter;
use Helpers\File\Adapters\PathResolverAdapter;
use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Symfony\Component\Console\Application;
use Testing\Concerns\InteractsWithConsole;

uses(InteractsWithConsole::class);

beforeEach(function () {
    // Setup Console App with commands manually
    $app = new Application();
    $app->add(new EnvEncryptCommand());
    $app->add(new EnvDecryptCommand());
    $this->setConsoleApplication($app);

    $storageDir = Paths::testPath('storage');
    if (! FileSystem::isDir($storageDir)) {
        FileSystem::mkdir($storageDir, 0755, true);
    }

    $this->envPath = Paths::testPath('storage/.env.test');
    $this->encryptedPath = Paths::testPath('storage/.env.test.encrypted');

    // Clean up before test
    if (FileSystem::exists($this->envPath)) {
        FileSystem::delete($this->envPath);
    }
    if (FileSystem::exists($this->encryptedPath)) {
        FileSystem::delete($this->encryptedPath);
    }
});

afterEach(function () {
    // Clean up or reset
    $this->consoleApp = null;

    // Clean up after test
    if (FileSystem::exists($this->envPath)) {
        FileSystem::delete($this->envPath);
    }
    if (FileSystem::exists($this->encryptedPath)) {
        FileSystem::delete($this->encryptedPath);
    }
});

afterAll(function () {
    $storageDir = Paths::testPath('storage');
    // Remove parent if empty
    if (FileSystem::isDir($storageDir) && count(glob($storageDir . DIRECTORY_SEPARATOR . '*')) === 0) {
        FileSystem::delete($storageDir);
    }
});

test('it can encrypt and decrypt env file', function () {
    // Create a dummy env file
    $content = "APP_NAME=TestApp\nAPP_KEY=base64:123456";
    FileSystem::put($this->envPath, $content);

    // Encrypt with command
    $key = random_bytes(32);
    $encodedKey = 'base64:' . base64_encode($key);

    $this->artisan('env:encrypt', [
        '--env' => 'tests/storage/.env.test',
        '--key' => $encodedKey,
    ])->assertCommandSuccessful();

    $this->assertFileExists($this->encryptedPath);

    // Delete original env file
    FileSystem::delete($this->envPath);

    // Decrypt
    $this->artisan('env:decrypt', [
        '--env' => 'tests/storage/.env.test',
        '--key' => $encodedKey,
    ])->assertCommandSuccessful();

    $this->assertFileExists($this->envPath);
    $this->assertEquals($content, FileSystem::get($this->envPath));
});

test('it can read encrypted env runtime', function () {
    $tempDir = Paths::testPath('storage/temp_env_test');
    if (! FileSystem::exists($tempDir)) {
        FileSystem::mkdir($tempDir, 0777, true);
    }

    $envFile = $tempDir . DIRECTORY_SEPARATOR . '.env';
    $encryptedFile = $envFile . '.encrypted';

    $content = "SECRET_VALUE=caffeine\nOTHER_VALUE=pizza";
    FileSystem::put($envFile, $content);

    $key = random_bytes(32);
    $encodedKey = 'base64:' . base64_encode($key);

    $this->artisan('env:encrypt', [
        '--env' => 'tests/storage/temp_env_test/.env',
        '--key' => $encodedKey,
        '--force' => true,
    ]);

    $this->assertCommandSuccessful();

    $this->assertFileExists($encryptedFile);

    FileSystem::delete($envFile);

    $_ENV['ANCHOR_ENV_ENCRYPTION_KEY'] = $encodedKey;

    $dotenv = new Dotenv(
        $tempDir,
        new EnvironmentAdapter(),
        new PathResolverAdapter(),
        new FileMetaAdapter(),
        new FileReadWriteAdapter(),
        new FileManipulationAdapter()
    );

    $dotenv->load();

    $this->assertEquals('caffeine', $dotenv->getValue('SECRET_VALUE'));
    $this->assertEquals('pizza', $dotenv->getValue('OTHER_VALUE'));

    // Clean up
    if (FileSystem::exists($encryptedFile)) {
        FileSystem::delete($encryptedFile);
    }
    if (FileSystem::exists($tempDir)) {
        FileSystem::delete($tempDir);
    }
    unset($_ENV['ANCHOR_ENV_ENCRYPTION_KEY']);
});
