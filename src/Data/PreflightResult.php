<?php

namespace hexa_package_publication_onboarding\Data;

use hexa_package_publication_onboarding\Support\SecretGuard;
use InvalidArgumentException;

final readonly class PreflightResult
{
    /**
     * @param array<string, mixed> $sourceInspection
     * @param list<HierarchyOption> $hierarchy
     */
    public function __construct(
        public array $sourceInspection,
        public array $hierarchy,
    ) {
        SecretGuard::assertSafe($this->sourceInspection);
        foreach ($this->hierarchy as $option) {
            if (! $option instanceof HierarchyOption) {
                throw new InvalidArgumentException('Preflight hierarchy values must be HierarchyOption objects.');
            }
        }
        $ids = array_map(static fn (HierarchyOption $option): int => $option->termId, $this->hierarchy);
        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidArgumentException('Preflight hierarchy term IDs must be unique.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_inspection' => $this->sourceInspection,
            'hierarchy' => array_map(
                static fn (HierarchyOption $option): array => $option->toArray(),
                $this->hierarchy,
            ),
            'top_level_available' => true,
        ];
    }
}
