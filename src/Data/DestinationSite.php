<?php

namespace hexa_package_publication_onboarding\Data;

use hexa_package_publication_onboarding\Support\SafeUrl;
use InvalidArgumentException;

final readonly class DestinationSite
{
    public string $canonicalOrigin;
    public string $wpAdminUrl;

    public function __construct(
        string $canonicalOrigin,
        string $wpAdminUrl,
        public string $installationId,
        public ?string $accountId = null,
        public ?string $siteId = null,
    ) {
        $this->canonicalOrigin = SafeUrl::origin($canonicalOrigin);
        $this->wpAdminUrl = SafeUrl::wpAdmin($wpAdminUrl, $this->canonicalOrigin);
        if (trim($this->installationId) === '') {
            throw new InvalidArgumentException('A destination installation ID is required.');
        }
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'canonical_origin' => $this->canonicalOrigin,
            'wp_admin_url' => $this->wpAdminUrl,
            'installation_id' => $this->installationId,
            'account_id' => $this->accountId,
            'site_id' => $this->siteId,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['canonical_origin'] ?? ''),
            (string) ($data['wp_admin_url'] ?? ''),
            (string) ($data['installation_id'] ?? ''),
            isset($data['account_id']) ? (string) $data['account_id'] : null,
            isset($data['site_id']) ? (string) $data['site_id'] : null,
        );
    }
}
