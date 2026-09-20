<?php

namespace hexa_package_publication_onboarding\Data;

use hexa_package_publication_onboarding\Support\SecretGuard;
use InvalidArgumentException;

final readonly class SystemReceipt
{
    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $rollbackReference
     */
    public function __construct(
        public string $system,
        public string $receiptId,
        public array $result,
        public array $rollbackReference,
    ) {
        if (! in_array($this->system, ['source', 'destination'], true) || trim($this->receiptId) === '') {
            throw new InvalidArgumentException('A valid system and receipt ID are required.');
        }
        SecretGuard::assertSafe($this->result);
        SecretGuard::assertSafe($this->rollbackReference);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'system' => $this->system,
            'receipt_id' => $this->receiptId,
            'result' => $this->result,
            'rollback_reference' => $this->rollbackReference,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['system'] ?? ''),
            (string) ($data['receipt_id'] ?? ''),
            is_array($data['result'] ?? null) ? $data['result'] : [],
            is_array($data['rollback_reference'] ?? null) ? $data['rollback_reference'] : [],
        );
    }
}
