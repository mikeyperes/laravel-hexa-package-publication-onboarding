<?php

namespace hexa_package_publication_onboarding\Support;

use InvalidArgumentException;

final class SafeUrl
{
    public static function origin(string $url): string
    {
        $parts = self::parts($url);
        $path = (string) ($parts['path'] ?? '');
        if ($path !== '' && $path !== '/') {
            throw new InvalidArgumentException('A canonical origin cannot contain a path.');
        }

        return 'https://'.strtolower((string) $parts['host']).self::port($parts);
    }

    public static function wpAdmin(string $url, string $canonicalOrigin): string
    {
        $parts = self::parts($url);
        $origin = 'https://'.strtolower((string) $parts['host']).self::port($parts);
        if (! hash_equals(self::origin($canonicalOrigin), $origin)) {
            throw new InvalidArgumentException('The WP Admin URL must use the destination origin.');
        }

        $path = '/'.ltrim((string) ($parts['path'] ?? ''), '/');
        if ($path === '/' || preg_match('#/(?:wp-admin(?:/.*)?|wp-login\.php)$#', $path) !== 1) {
            throw new InvalidArgumentException('A safe WP Admin or wp-login URL is required.');
        }

        return $origin.$path;
    }

    public static function https(string $url): string
    {
        $parts = self::parts($url);
        $path = (string) ($parts['path'] ?? '');

        return 'https://'.strtolower((string) $parts['host']).self::port($parts).($path === '' ? '/' : $path);
    }

    public static function sameOrigin(string $url, string $canonicalOrigin): bool
    {
        $parts = self::parts($url);
        $origin = 'https://'.strtolower((string) $parts['host']).self::port($parts);

        return hash_equals(self::origin($canonicalOrigin), $origin);
    }

    /** @return array<string, mixed> */
    private static function parts(string $url): array
    {
        $url = trim($url);
        $parts = parse_url($url);
        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) {
            throw new InvalidArgumentException('A valid HTTPS URL is required.');
        }
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException('URLs cannot contain credentials, queries, or fragments.');
        }

        return $parts;
    }

    /** @param array<string, mixed> $parts */
    private static function port(array $parts): string
    {
        $port = isset($parts['port']) ? (int) $parts['port'] : 443;
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('The URL port is invalid.');
        }

        return $port === 443 ? '' : ':'.$port;
    }
}
