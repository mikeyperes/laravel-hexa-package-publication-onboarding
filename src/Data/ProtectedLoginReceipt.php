<?php

namespace hexa_package_publication_onboarding\Data;

use DateTimeImmutable;
use hexa_package_publication_onboarding\Support\SecretGuard;
use InvalidArgumentException;

final readonly class ProtectedLoginReceipt
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $receiptId,
        public string $installationId,
        public string $validationStatus,
        public string $validatedAt,
        public array $metadata = [],
    ) {
        if (trim($this->receiptId) === '' || trim($this->installationId) === '') {
            throw new InvalidArgumentException('A safe Toolkit receipt and installation ID are required.');
        }
        if ($this->validationStatus !== 'valid') {
            throw new InvalidArgumentException('Only a positively validated Toolkit login receipt is accepted.');
        }
        if (DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $this->validatedAt) === false) {
            throw new InvalidArgumentException('The Toolkit validation timestamp must be ISO-8601.');
        }
        SecretGuard::assertSafe($this->metadata);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'receipt_id' => $this->receiptId,
            'installation_id' => $this->installationId,
            'validation_status' => $this->validationStatus,
            'validated_at' => $this->validatedAt,
            'metadata' => $this->metadata,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['receipt_id'] ?? ''),
            (string) ($data['installation_id'] ?? ''),
            (string) ($data['validation_status'] ?? ''),
            (string) ($data['validated_at'] ?? ''),
            is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }
}
