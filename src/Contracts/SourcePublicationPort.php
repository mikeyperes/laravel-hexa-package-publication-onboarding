<?php

namespace hexa_package_publication_onboarding\Contracts;

use hexa_package_publication_onboarding\Data\HierarchyOption;
use hexa_package_publication_onboarding\Data\OnboardingRequest;
use hexa_package_publication_onboarding\Data\SystemPlan;
use hexa_package_publication_onboarding\Data\SystemReceipt;
use hexa_package_publication_onboarding\Data\SystemRollbackResult;
use hexa_package_publication_onboarding\Data\SystemVerification;
use hexa_package_publication_onboarding\Data\OutletIdentity;

interface SourcePublicationPort
{
    /** @return array<string, mixed> */
    public function inspect(OutletIdentity $outlet): array;

    /** @return list<HierarchyOption> */
    public function hierarchy(): array;

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
