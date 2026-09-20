<?php

namespace hexa_package_publication_onboarding\Data;

use hexa_package_publication_onboarding\Support\SecretGuard;
use InvalidArgumentException;

final readonly class SystemRollbackResult
{
    /** @param array<string, mixed> $readback */
    public function __construct(
        public string $system,
        public bool $success,
        public array $readback,
        public string $blocker = '',
    ) {
        if (! in_array($this->system, ['source', 'destination'], true)) {
            throw new InvalidArgumentException('A rollback result must target source or destination.');
        }
        if (! $this->success && trim($this->blocker) === '') {
            throw new InvalidArgumentException('A failed rollback requires a safe blocker.');
        }
        SecretGuard::assertSafe($this->readback);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'system' => $this->system,
            'success' => $this->success,
            'readback' => $this->readback,
            'blocker' => $this->blocker,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['system'] ?? ''),
            (bool) ($data['success'] ?? false),
            is_array($data['readback'] ?? null) ? $data['readback'] : [],
            (string) ($data['blocker'] ?? ''),
        );
    }
}
