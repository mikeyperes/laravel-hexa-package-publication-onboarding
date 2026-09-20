<?php

namespace hexa_package_publication_onboarding\Tests\Support;

use hexa_package_publication_onboarding\Contracts\OnboardingOperationStore;
use hexa_package_publication_onboarding\Data\OnboardingOperation;

final class InMemoryOperationStore implements OnboardingOperationStore
{
    /** @var array<string, OnboardingOperation> */
    private array $operations = [];

    public function find(string $runId): ?OnboardingOperation
    {
        return $this->operations[$runId] ?? null;
    }

    public function save(OnboardingOperation $operation): void
    {
        $this->operations[$operation->plan->request->runId] = $operation;
    }
}
