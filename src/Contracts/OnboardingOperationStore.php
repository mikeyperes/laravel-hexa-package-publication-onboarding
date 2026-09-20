<?php

namespace hexa_package_publication_onboarding\Contracts;

use hexa_package_publication_onboarding\Data\OnboardingOperation;

interface OnboardingOperationStore
{
    public function find(string $runId): ?OnboardingOperation;

    public function save(OnboardingOperation $operation): void;
}
