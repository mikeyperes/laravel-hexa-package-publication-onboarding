<?php

namespace hexa_package_publication_onboarding\Data;

use hexa_package_publication_onboarding\Enums\OnboardingState;
use hexa_package_publication_onboarding\Support\SecretGuard;
use InvalidArgumentException;

final readonly class OnboardingOperation
{
    /**
     * @param array<string, SystemReceipt> $receipts
     * @param array<string, SystemVerification> $verifications
     * @param array<string, SystemRollbackResult> $rollbacks
     */
    public function __construct(
        public string $operationId,
        public string $approvalReceiptId,
        public OnboardingPlan $plan,
        public OnboardingState $state,
        public array $receipts,
        public array $verifications,
        public array $rollbacks,
        public string $error,
        public string $updatedAt,
    ) {
        if (trim($this->operationId) === '' || trim($this->approvalReceiptId) === '' || trim($this->updatedAt) === '') {
            throw new InvalidArgumentException('Operation, approval receipt, and update identifiers are required.');
        }
        foreach ($this->receipts as $system => $receipt) {
            if (! $receipt instanceof SystemReceipt || $receipt->system !== $system) {
                throw new InvalidArgumentException('Operation receipts must be keyed by their system.');
            }
        }
        foreach ($this->verifications as $system => $verification) {
            if (! $verification instanceof SystemVerification || $verification->system !== $system) {
                throw new InvalidArgumentException('Operation verifications must be keyed by their system.');
            }
        }
        foreach ($this->rollbacks as $system => $rollback) {
            if (! $rollback instanceof SystemRollbackResult || $rollback->system !== $system) {
                throw new InvalidArgumentException('Operation rollback results must be keyed by their system.');
            }
        }
        SecretGuard::assertSafe(['error' => $this->error]);
    }

    public static function start(string $operationId, string $approvalReceiptId, OnboardingPlan $plan, string $now): self
    {
        return new self($operationId, $approvalReceiptId, $plan, OnboardingState::Planned, [], [], [], '', $now);
    }

    public function withState(OnboardingState $state, string $now): self
    {
        return new self(
            $this->operationId,
            $this->approvalReceiptId,
            $this->plan,
            $state,
            $this->receipts,
            $this->verifications,
            $this->rollbacks,
            $this->error,
            $now,
        );
    }

    public function withReceipt(SystemReceipt $receipt, OnboardingState $state, string $now): self
    {
        $receipts = $this->receipts;
        $receipts[$receipt->system] = $receipt;

        return new self(
            $this->operationId,
            $this->approvalReceiptId,
            $this->plan,
            $state,
            $receipts,
            $this->verifications,
            $this->rollbacks,
            $this->error,
            $now,
        );
    }

    public function withVerification(SystemVerification $verification, string $now): self
    {
        $verifications = $this->verifications;
        $verifications[$verification->system] = $verification;

        return new self(
            $this->operationId,
            $this->approvalReceiptId,
            $this->plan,
            $this->state,
            $this->receipts,
            $verifications,
            $this->rollbacks,
            $this->error,
            $now,
        );
    }

    public function withRollback(SystemRollbackResult $rollback, string $now): self
    {
        $rollbacks = $this->rollbacks;
        $rollbacks[$rollback->system] = $rollback;

        return new self(
            $this->operationId,
            $this->approvalReceiptId,
            $this->plan,
            $this->state,
            $this->receipts,
            $this->verifications,
            $rollbacks,
            $this->error,
            $now,
        );
    }

    public function withError(string $error, OnboardingState $state, string $now): self
    {
        return new self(
            $this->operationId,
            $this->approvalReceiptId,
            $this->plan,
            $state,
            $this->receipts,
            $this->verifications,
            $this->rollbacks,
            trim($error),
            $now,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'operation_id' => $this->operationId,
            'approval_receipt_id' => $this->approvalReceiptId,
            'plan' => $this->plan->toArray(),
            'state' => $this->state->value,
            'receipts' => array_map(static fn (SystemReceipt $receipt): array => $receipt->toArray(), $this->receipts),
            'verifications' => array_map(static fn (SystemVerification $verification): array => $verification->toArray(), $this->verifications),
            'rollbacks' => array_map(static fn (SystemRollbackResult $rollback): array => $rollback->toArray(), $this->rollbacks),
            'error' => $this->error,
            'updated_at' => $this->updatedAt,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $receipts = [];
        foreach (is_array($data['receipts'] ?? null) ? $data['receipts'] : [] as $system => $receipt) {
            $receipts[(string) $system] = SystemReceipt::fromArray(is_array($receipt) ? $receipt : []);
        }
        $verifications = [];
        foreach (is_array($data['verifications'] ?? null) ? $data['verifications'] : [] as $system => $verification) {
            $verifications[(string) $system] = SystemVerification::fromArray(is_array($verification) ? $verification : []);
        }
        $rollbacks = [];
        foreach (is_array($data['rollbacks'] ?? null) ? $data['rollbacks'] : [] as $system => $rollback) {
            $rollbacks[(string) $system] = SystemRollbackResult::fromArray(is_array($rollback) ? $rollback : []);
        }

        return new self(
            (string) ($data['operation_id'] ?? ''),
            (string) ($data['approval_receipt_id'] ?? ''),
            OnboardingPlan::fromArray(is_array($data['plan'] ?? null) ? $data['plan'] : []),
            OnboardingState::from((string) ($data['state'] ?? '')),
            $receipts,
            $verifications,
            $rollbacks,
            (string) ($data['error'] ?? ''),
            (string) ($data['updated_at'] ?? ''),
        );
    }
}
