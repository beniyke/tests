<?php

declare(strict_types=1);

namespace Tests\System\Support\Security;

use Helpers\File\Contracts\CacheInterface;
use Security\Firewall\Drivers\BaseFirewall;

/**
 * A testable Firewall subclass that exposes protected methods for testing
 */
class TestFirewall extends BaseFirewall
{
    public function handle(): void
    {
        // Implementation for testing
    }

    // Expose protected methods for testing
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
