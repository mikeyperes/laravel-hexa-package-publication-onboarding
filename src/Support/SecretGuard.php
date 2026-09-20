<?php

namespace hexa_package_publication_onboarding\Support;

use InvalidArgumentException;
use Throwable;

final class SecretGuard
{
    private const FORBIDDEN_KEYS = [
        'authorization', 'cookie', 'credential_value', 'client_secret',
        'password', 'passwd', 'private_key', 'recovery_code', 'secret',
        'token', 'otp', 'username', 'user_name',
    ];

    /** @param array<string|int, mixed> $payload */
    public static function assertSafe(array $payload): void
    {
        self::walk($payload, 0);
    }

    public static function exceptionMessage(Throwable $throwable): string
    {
        $message = trim($throwable->getMessage());
        $message = preg_replace('/([?&][^\s=]+)=([^\s&]+)/', '$1=[redacted]', $message) ?? $message;
        $message = preg_replace('/\b(Bearer|Basic)\s+[^\s]+/i', '$1 [redacted]', $message) ?? $message;

        $fallback = basename(str_replace('\\', '/', $throwable::class));

        return mb_substr($message !== '' ? $message : $fallback, 0, 500);
    }

    private static function walk(mixed $value, int $depth): void
    {
        if ($depth > 20) {
            throw new InvalidArgumentException('The onboarding payload is nested too deeply.');
        }
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (is_string($key) && self::forbiddenKey($key)) {
                    throw new InvalidArgumentException("Secret-bearing field '{$key}' is forbidden.");
                }
                self::walk($item, $depth + 1);
            }

            return;
        }
        if (is_object($value) || is_resource($value)) {
            throw new InvalidArgumentException('Onboarding payloads must contain serializable scalar values only.');
        }
        if (is_string($value) && preg_match('/\b(Bearer|Basic)\s+[A-Za-z0-9+\/_=.:-]+/i', $value) === 1) {
            throw new InvalidArgumentException('Authorization material is forbidden in onboarding payloads.');
        }
    }

    private static function forbiddenKey(string $key): bool
    {
        $normalized = strtolower(trim($key));
        foreach (self::FORBIDDEN_KEYS as $forbidden) {
            if ($normalized === $forbidden || str_ends_with($normalized, '_'.$forbidden)) {
                return true;
            }
        }

        return false;
    }
}
