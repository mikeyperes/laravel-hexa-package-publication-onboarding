<?php

namespace hexa_package_publication_onboarding\Contracts;

use hexa_package_publication_onboarding\Data\OnboardingRequest;
use hexa_package_publication_onboarding\Data\SystemPlan;
use hexa_package_publication_onboarding\Data\SystemReceipt;
use hexa_package_publication_onboarding\Data\SystemRollbackResult;
use hexa_package_publication_onboarding\Data\SystemVerification;

interface DestinationPublicationPort
{
    public function plan(OnboardingRequest $request): SystemPlan;

    public function apply(OnboardingRequest $request, SystemPlan $plan, string $operationId): SystemReceipt;

    public function verify(OnboardingRequest $request, ?SystemReceipt $receipt): SystemVerification;

    public function rollback(
        OnboardingRequest $request,
        SystemPlan $plan,
        SystemReceipt $receipt,
        string $operationId,
    ): SystemRollbackResult;
}
