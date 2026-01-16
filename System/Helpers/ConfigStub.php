<?php

declare(strict_types=1);

namespace Tests\System\Helpers;

use Core\Services\ConfigServiceInterface;

/**
 * A simple config stub for testing that returns sensible defaults
 * without relying on the global container.
 */
class ConfigStub implements ConfigServiceInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'timezone' => 'UTC',
        ], $config);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (! is_array($value) || ! array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function all(): array
    {
        return $this->config;
    }

    public function isDebugEnabled(): bool
    {
        return $this->get('debug', true);
    }

    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$this->config;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (!isset($current[$k]) || !is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }
    }
}
