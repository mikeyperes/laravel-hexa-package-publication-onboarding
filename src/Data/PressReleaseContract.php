<?php

namespace hexa_package_publication_onboarding\Data;

use InvalidArgumentException;

final readonly class PressReleaseContract
{
    private const STRUCTURAL_FIELDS = [
        'headline',
        'dateline',
        'lead',
        'body',
        'boilerplate',
        'media_contact',
        'canonical_url',
        'published_at',
        'image',
    ];

    /** @param list<string> $requiredFields */
    public function __construct(
        public string $version,
        public array $requiredFields,
    ) {
        if (trim($this->version) === '') {
            throw new InvalidArgumentException('A press-release contract version is required.');
        }
        $normalized = array_values(array_unique(array_map(
            static fn (mixed $field): string => strtolower(trim((string) $field)),
            $this->requiredFields,
        )));
        if ($normalized !== $this->requiredFields || array_diff(self::STRUCTURAL_FIELDS, $normalized) !== []) {
            throw new InvalidArgumentException('The press-release contract is missing required structural fields or is not normalized.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'required_fields' => $this->requiredFields,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['version'] ?? ''),
            array_values(array_map('strval', is_array($data['required_fields'] ?? null) ? $data['required_fields'] : [])),
        );
    }
}
