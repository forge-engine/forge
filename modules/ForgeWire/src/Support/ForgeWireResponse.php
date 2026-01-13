<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Support;

final class ForgeWireResponse
{
    private static array $contexts = [];

    private ?array $redirect = null;
    private array $flashes = [];
    private array $events = [];

    public static function getContext(string $id): ?self
    {
        return self::$contexts[$id] ?? null;
    }

    public static function setContext(string $id, self $context): void
    {
        self::$contexts[$id] = $context;
    }

    public static function clearContext(string $id): void
    {
        unset(self::$contexts[$id]);
    }

    public static function getCurrentContextId(): ?string
    {
        $ids = array_keys(self::$contexts);
        return end($ids) ?: null;
    }

    public function setRedirect(string $url, int $delay = 0): void
    {
        if ($delay < 0) {
            throw new \InvalidArgumentException("Redirect delay must be a non-negative integer");
        }
        
        $this->redirect = [
            'url' => $this->validateRedirect($url),
            'delay' => $delay,
        ];
    }

    public function addFlash(string $type, string $message): void
    {
        $validTypes = ['success', 'error', 'info', 'warning'];
        if (!in_array($type, $validTypes, true)) {
            throw new \InvalidArgumentException("Invalid flash type: {$type}. Must be one of: " . implode(', ', $validTypes));
        }

        $this->flashes[] = [
            'type' => $type,
            'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
        ];
    }

    public function addEvent(string $event, array $data = []): void
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $event)) {
            throw new \InvalidArgumentException("Invalid event name: {$event}. Only alphanumeric, underscore, and hyphen characters allowed.");
        }

        $this->events[] = [
            'name' => $event,
            'data' => $this->sanitizeEventData($data),
        ];
    }

    public function getRedirect(): ?array
    {
        return $this->redirect;
    }

    public function getFlashes(): array
    {
        return $this->flashes;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    private function validateRedirect(string $url): string
    {
        if (str_starts_with($url, '/')) {
            return $url;
        }

        $allowedDomains = $this->getAllowedDomains();
        if (empty($allowedDomains)) {
            throw new \InvalidArgumentException("Redirect URL must be relative (start with '/') or domain must be whitelisted: {$url}");
        }

        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            throw new \InvalidArgumentException("Invalid redirect URL: {$url}");
        }

        if (!in_array($parsed['scheme'], ['http', 'https'], true)) {
            throw new \InvalidArgumentException("Redirect URL must use http or https scheme: {$url}");
        }

        $host = $parsed['host'];
        foreach ($allowedDomains as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return $url;
            }
        }

        throw new \InvalidArgumentException("Redirect domain not whitelisted: {$host}");
    }

    private function getAllowedDomains(): array
    {
        $envDomains = env('FORGEWIRE_REDIRECT_ALLOWED_DOMAINS', '');
        if ($envDomains !== '') {
            return array_filter(array_map('trim', explode(',', $envDomains)));
        }

        return [];
    }

    private function sanitizeEventData(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (!is_string($key) || !preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
                continue;
            }

            if (is_scalar($value) || is_null($value)) {
                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeEventData($value);
            }
        }
        return $sanitized;
    }
}
