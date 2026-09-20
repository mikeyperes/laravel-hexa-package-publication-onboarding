<?php

namespace hexa_package_publication_onboarding\Data;

use hexa_package_publication_onboarding\Support\Fingerprint;
use hexa_package_publication_onboarding\Support\SecretGuard;
use InvalidArgumentException;

final readonly class SystemPlan
{
    public string $fingerprint;

    /**
     * @param list<array<string, mixed>> $actions
     * @param array<string, mixed> $beforeState
     * @param array<string, mixed> $rollbackState
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $system,
        public array $actions,
        public array $beforeState,
        public array $rollbackState,
        public array $metadata = [],
    ) {
        if (! in_array($this->system, ['source', 'destination'], true)) {
            throw new InvalidArgumentException('A system plan must target source or destination.');
        }
        if ($this->actions === []) {
            throw new InvalidArgumentException("The {$this->system} plan requires at least one action.");
        }
        $ids = [];
        foreach ($this->actions as $action) {
            if (! is_array($action)) {
                throw new InvalidArgumentException('Each planned action must be an array.');
            }
            $id = (string) ($action['id'] ?? '');
            if (preg_match('/^[a-z][a-z0-9._:-]{2,127}$/', $id) !== 1) {
                throw new InvalidArgumentException('Each planned action requires a safe action ID.');
            }
            $ids[] = $id;
        }
        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidArgumentException('Planned action IDs must be unique within a system.');
        }
        SecretGuard::assertSafe($this->actions);
        SecretGuard::assertSafe($this->beforeState);
        SecretGuard::assertSafe($this->rollbackState);
        SecretGuard::assertSafe($this->metadata);
        $this->fingerprint = Fingerprint::of($this->fingerprintPayload());
    }

    /** @return list<string> */
    public function actionIds(): array
    {
        return array_map(static fn (array $action): string => (string) $action['id'], $this->actions);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->fingerprintPayload() + ['fingerprint' => $this->fingerprint];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $plan = new self(
            (string) ($data['system'] ?? ''),
            is_array($data['actions'] ?? null) ? array_values($data['actions']) : [],
            is_array($data['before_state'] ?? null) ? $data['before_state'] : [],
            is_array($data['rollback_state'] ?? null) ? $data['rollback_state'] : [],
            is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
        $stored = (string) ($data['fingerprint'] ?? $plan->fingerprint);
        if (! hash_equals($plan->fingerprint, $stored)) {
            throw new InvalidArgumentException('The stored system-plan fingerprint does not match its payload.');
        }

        return $plan;
    }

    /** @return array<string, mixed> */
    private function fingerprintPayload(): array
    {
        return [
            'system' => $this->system,
            'actions' => $this->actions,
            'before_state' => $this->beforeState,
            'rollback_state' => $this->rollbackState,
            'metadata' => $this->metadata,
        ];
    }
}
