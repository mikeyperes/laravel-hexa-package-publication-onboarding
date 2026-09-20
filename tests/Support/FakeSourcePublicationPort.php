<?php

namespace hexa_package_publication_onboarding\Tests\Support;

use ArrayObject;
use hexa_package_publication_onboarding\Contracts\SourcePublicationPort;
use hexa_package_publication_onboarding\Data\HierarchyOption;
use hexa_package_publication_onboarding\Data\OnboardingRequest;
use hexa_package_publication_onboarding\Data\OutletIdentity;
use hexa_package_publication_onboarding\Data\SystemPlan;
use hexa_package_publication_onboarding\Data\SystemReceipt;
use hexa_package_publication_onboarding\Data\SystemRollbackResult;
use hexa_package_publication_onboarding\Data\SystemVerification;

final class FakeSourcePublicationPort implements SourcePublicationPort
{
    /** @param ArrayObject<int, string> $events */
    public function __construct(private readonly ArrayObject $events)
    {
    }

    public function inspect(OutletIdentity $outlet): array
    {
        return ['existing' => false, 'canonical_origin' => $outlet->canonicalOrigin];
    }

    public function hierarchy(): array
    {
        return [new HierarchyOption(10, 'Hexa', 'Hexa')];
    }

    public function plan(OnboardingRequest $request): SystemPlan
    {
        return new SystemPlan(
            'source',
            [
                ['id' => 'source.upload_branding'],
                ['id' => 'source.upsert_outlet'],
            ],
            ['outlet' => null, 'branding' => []],
            ['remove_created_outlet' => true, 'retain_unreferenced_media' => true],
        );
    }

    public function apply(OnboardingRequest $request, SystemPlan $plan, string $operationId): SystemReceipt
    {
        $this->events->append('source.apply');

        return new SystemReceipt('source', 'source-receipt', ['outlet_id' => 99], ['outlet_id' => 99]);
    }

    public function verify(OnboardingRequest $request, ?SystemReceipt $receipt): SystemVerification
    {
        $this->events->append('source.verify');

        return new SystemVerification('source', true, [
            'outlet_id' => 99,
            'hierarchy_path' => 'Hexa',
            'feed_endpoint' => 'https://hexaprwire.com/distribution/outlets/test/feed',
            'source_contract_version' => 'core-api-1.0',
            'hierarchy_revision' => 'hierarchy-revision-1',
            'press_release_contract_version' => '1.0',
            'logo_url' => 'https://hexaprwire.com/wp-content/uploads/logo.png',
            'icon_url' => 'https://hexaprwire.com/wp-content/uploads/icon.png',
            'logo_attachment_id' => 501,
            'icon_attachment_id' => 502,
        ]);
    }

    public function rollback(
        OnboardingRequest $request,
        SystemPlan $plan,
        SystemReceipt $receipt,
        string $operationId,
    ): SystemRollbackResult {
        $this->events->append('source.rollback');

        return new SystemRollbackResult('source', true, ['outlet_restored' => true]);
    }
}
