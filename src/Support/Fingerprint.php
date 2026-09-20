<?php

namespace hexa_package_publication_onboarding\Support;

final class Fingerprint
{
    /** @param array<string|int, mixed> $value */
    public static function of(array $value): string
    {
        return hash('sha256', CanonicalJson::encode($value));
    }
}
