<?php

namespace hexa_package_publication_onboarding\Data;

use hexa_package_publication_onboarding\Support\SafeUrl;
use InvalidArgumentException;

final readonly class OutletIdentity
{
    public string $displayName;
    public string $slug;
    public string $canonicalOrigin;

    public function __construct(
        string $displayName,
        string $slug,
        string $canonicalOrigin,
    ) {
        $this->displayName = trim($displayName);
        $this->slug = strtolower(trim($slug));
        $this->canonicalOrigin = SafeUrl::origin($canonicalOrigin);
        if ($this->displayName === '' || mb_strlen($this->displayName) > 160) {
            throw new InvalidArgumentException('A valid outlet display name is required.');
        }
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $this->slug) !== 1) {
            throw new InvalidArgumentException('The outlet slug must be lowercase hyphen-case.');
        }
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'display_name' => $this->displayName,
            'slug' => $this->slug,
            'canonical_origin' => $this->canonicalOrigin,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['display_name'] ?? ''),
            (string) ($data['slug'] ?? ''),
            (string) ($data['canonical_origin'] ?? ''),
        );
    }
}
