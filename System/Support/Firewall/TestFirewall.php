<?php

declare(strict_types=1);

namespace Tests\Support\Firewall;

use Helpers\File\Contracts\CacheInterface;
use Security\Firewall\Drivers\BaseFirewall;

class TestFirewall extends BaseFirewall
{
    public function handle(): void
    {
        // Implementation for testing
    }

    public function publicGetConfig(?string $value = null): array
    {
        return $this->getConfig($value);
    }

    public function publicCache(): CacheInterface
    {
        return $this->cache();
    }

    public function publicAuditTrail(string $message, ?array $identifier = null): void
    {
        $this->auditTrail($message, $identifier);
    }

    public function publicSetResponse(array $response): void
    {
        $this->setResponse($response);
    }

    public function publicGetViewResponsePayload(string $template, int $code = 200): array
    {
        return $this->getViewResponsePayload($template, $code);
    }

    public function publicGetJsonResponsePayload(array $content, int $code): array
    {
        return $this->getJsonResponsePayload($content, $code);
    }

    public function publicGetRedirectResponsePayload(string $route): array
    {
        return $this->getRedirectResponsePayload($route);
    }
}
