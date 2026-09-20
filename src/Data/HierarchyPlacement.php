<?php

namespace hexa_package_publication_onboarding\Data;

use InvalidArgumentException;

final readonly class HierarchyPlacement
{
    private function __construct(
        public bool $topLevel,
        public ?int $parentTermId,
        public string $parentPath,
    ) {
        if (! $topLevel && (($parentTermId ?? 0) < 1 || trim($parentPath) === '')) {
            throw new InvalidArgumentException('A non-top-level placement requires a parent term ID and full path.');
        }
        if ($topLevel && ($parentTermId !== null || trim($parentPath) !== 'Top level')) {
            throw new InvalidArgumentException('Top-level placement must use the canonical Top level sentinel.');
        }
    }

    public static function topLevel(): self
    {
        return new self(true, null, 'Top level');
    }

    public static function under(int $parentTermId, string $parentPath): self
    {
        return new self(false, $parentTermId, trim($parentPath));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'top_level' => $this->topLevel,
            'parent_term_id' => $this->parentTermId,
            'parent_path' => $this->parentPath,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return (bool) ($data['top_level'] ?? false)
            ? self::topLevel()
            : self::under((int) ($data['parent_term_id'] ?? 0), (string) ($data['parent_path'] ?? ''));
    }
}
