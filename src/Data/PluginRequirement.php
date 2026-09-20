<?php

namespace hexa_package_publication_onboarding\Data;

use InvalidArgumentException;

final readonly class PluginRequirement
{
    public function __construct(
        public string $basename,
        public string $version,
        public bool $mustBeActive = true,
    ) {
        if (preg_match('#^[a-z0-9-]+/[A-Za-z0-9._-]+\.php$#', trim($this->basename)) !== 1) {
            throw new InvalidArgumentException('A canonical WordPress plugin basename is required.');
        }
        if (trim($this->version) === '') {
            throw new InvalidArgumentException('A reviewed Distributor version is required.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'basename' => $this->basename,
            'version' => $this->version,
            'must_be_active' => $this->mustBeActive,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['basename'] ?? ''),
            (string) ($data['version'] ?? ''),
            (bool) ($data['must_be_active'] ?? true),
        );
    }
}
