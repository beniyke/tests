<?php

declare(strict_types=1);

namespace Tests\System\Console;

use Core\Services\Dotenv;
use Core\Support\Adapters\EnvironmentAdapter;
use Helpers\File\Adapters\FileManipulationAdapter;
use Helpers\File\Adapters\FileMetaAdapter;
use Helpers\File\Adapters\FileReadWriteAdapter;
use Helpers\File\Adapters\PathResolverAdapter;
use Helpers\File\Paths;
use Testing\Concerns\InteractsWithConsole;
use Tests\TestCase;

class EnvEncryptionTest extends TestCase
{
    use InteractsWithConsole;

    protected string $envPath;

    protected string $encryptedPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Console App with commands manually
        $app = new \Symfony\Component\Console\Application();
        $app->add(new \Cli\Commands\Runners\EnvEncryptCommand());
        $app->add(new \Cli\Commands\Runners\EnvDecryptCommand());
        $this->setConsoleApplication($app);

        $this->envPath = Paths::basePath('.env.test');
        $this->encryptedPath = Paths::basePath('.env.test.encrypted');

        // Clean up before test
        if (file_exists($this->envPath)) {
            unlink($this->envPath);
        }
        if (file_exists($this->encryptedPath)) {
            unlink($this->encryptedPath);
        }
    }

    protected function tearDown(): void
    {
        // Clean up or reset
        $this->consoleApp = null;

        // Clean up after test
        if (file_exists($this->envPath)) {
            unlink($this->envPath);
        }
        if (file_exists($this->encryptedPath)) {
            unlink($this->encryptedPath);
        }
        parent::tearDown();
    }

    public function test_it_can_encrypt_and_decrypt_env_file()
    {
        // Create a dummy env file
        $content = "APP_NAME=TestApp\nAPP_KEY=base64:123456";
        file_put_contents($this->envPath, $content);

        // Encrypt with command
        $key = random_bytes(32);
        $encodedKey = 'base64:' . base64_encode($key);

        $this->artisan('env:encrypt', [
            '--env' => '.env.test',
            '--key' => $encodedKey,
        ])->assertCommandSuccessful();

        $this->assertFileExists($this->encryptedPath);

        // Delete original env file
        unlink($this->envPath);

        // Decrypt
        $this->artisan('env:decrypt', [
            '--env' => '.env.test',
            '--key' => $encodedKey,
        ])->assertCommandSuccessful();

        $this->assertFileExists($this->envPath);
        $this->assertEquals($content, file_get_contents($this->envPath));
    }

    public function test_it_can_read_encrypted_env_runtime()
    {
        $tempDir = Paths::basePath('storage/temp_env_test');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $envFile = $tempDir . DIRECTORY_SEPARATOR . '.env';
        $encryptedFile = $envFile . '.encrypted';

        $content = "SECRET_VALUE=caffeine\nOTHER_VALUE=pizza";
        file_put_contents($envFile, $content);

        $key = random_bytes(32);
        $encodedKey = 'base64:' . base64_encode($key);

        $this->artisan('env:encrypt', [
            '--env' => 'storage/temp_env_test/.env',
            '--key' => $encodedKey,
            '--force' => true,
        ]);

        $this->assertCommandSuccessful();

        $this->assertFileExists($encryptedFile);

        unlink($envFile);

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
        if (file_exists($encryptedFile)) {
            unlink($encryptedFile);
        }
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
        unset($_ENV['ANCHOR_ENV_ENCRYPTION_KEY']);
    }
}
