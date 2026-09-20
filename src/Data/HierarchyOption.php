<?php

namespace hexa_package_publication_onboarding\Data;

use InvalidArgumentException;

final readonly class HierarchyOption
{
    public function __construct(
        public int $termId,
        public string $name,
        public string $path,
        public ?int $parentTermId = null,
    ) {
        if ($this->termId < 1 || trim($this->name) === '' || trim($this->path) === '') {
            throw new InvalidArgumentException('Hierarchy options require a positive term ID, name, and full path.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'term_id' => $this->termId,
            'name' => $this->name,
            'path' => $this->path,
            'parent_term_id' => $this->parentTermId,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['term_id'] ?? 0),
            (string) ($data['name'] ?? ''),
            (string) ($data['path'] ?? ''),
            isset($data['parent_term_id']) ? (int) $data['parent_term_id'] : null,
        );
    }
}
