<?php

namespace hexa_package_publication_onboarding\Data;

use hexa_package_publication_onboarding\Support\SafeUrl;
use InvalidArgumentException;

final readonly class VerificationRelease
{
    public string $canonicalUrl;

    public function __construct(
        public string $sourceReleaseId,
        string $canonicalUrl,
    ) {
        if (trim($this->sourceReleaseId) === '') {
            throw new InvalidArgumentException('A source release ID is required for live verification.');
        }
        $this->canonicalUrl = SafeUrl::https($canonicalUrl);
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'source_release_id' => $this->sourceReleaseId,
            'canonical_url' => $this->canonicalUrl,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['source_release_id'] ?? ''),
            (string) ($data['canonical_url'] ?? ''),
        );
    }
}
