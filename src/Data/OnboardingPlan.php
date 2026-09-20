<?php

namespace hexa_package_publication_onboarding\Data;

use hexa_package_publication_onboarding\Support\Fingerprint;
use InvalidArgumentException;

final readonly class OnboardingPlan
{
    private function __construct(
        public OnboardingRequest $request,
        public SystemPlan $source,
        public SystemPlan $destination,
        public array $actionIds,
        public string $rollbackDigest,
        public string $fingerprint,
    ) {
    }

    public static function compile(
        OnboardingRequest $request,
        SystemPlan $source,
        SystemPlan $destination,
    ): self {
        if ($source->system !== 'source' || $destination->system !== 'destination') {
            throw new InvalidArgumentException('The onboarding plan requires source and destination system plans.');
        }

        $actionIds = array_merge($source->actionIds(), $destination->actionIds());
        if ($actionIds !== $request->requestedActions) {
            throw new InvalidArgumentException('The planned action order does not match the immutable requested action set.');
        }

        $rollbackDigest = Fingerprint::of([
            'source_before' => $source->beforeState,
            'source_rollback' => $source->rollbackState,
            'destination_before' => $destination->beforeState,
            'destination_rollback' => $destination->rollbackState,
        ]);
        $payload = [
            'request' => $request->toArray(),
            'source' => $source->toArray(),
            'destination' => $destination->toArray(),
            'action_ids' => $actionIds,
            'rollback_digest' => $rollbackDigest,
        ];

        return new self(
            $request,
            $source,
            $destination,
            $actionIds,
            $rollbackDigest,
            Fingerprint::of($payload),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'request' => $this->request->toArray(),
            'source' => $this->source->toArray(),
            'destination' => $this->destination->toArray(),
            'action_ids' => $this->actionIds,
            'rollback_digest' => $this->rollbackDigest,
            'fingerprint' => $this->fingerprint,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $plan = self::compile(
            OnboardingRequest::fromArray(is_array($data['request'] ?? null) ? $data['request'] : []),
            SystemPlan::fromArray(is_array($data['source'] ?? null) ? $data['source'] : []),
            SystemPlan::fromArray(is_array($data['destination'] ?? null) ? $data['destination'] : []),
        );
        $storedFingerprint = (string) ($data['fingerprint'] ?? '');
        $storedRollback = (string) ($data['rollback_digest'] ?? '');
        if (! hash_equals($plan->fingerprint, $storedFingerprint) || ! hash_equals($plan->rollbackDigest, $storedRollback)) {
            throw new InvalidArgumentException('The stored onboarding plan does not match its immutable payload.');
        }

        return $plan;
    }
}
