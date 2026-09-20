<?php

namespace hexa_package_publication_onboarding\Tests\Support;

use ArrayObject;
use hexa_package_publication_onboarding\Contracts\DestinationPublicationPort;
use hexa_package_publication_onboarding\Data\OnboardingRequest;
use hexa_package_publication_onboarding\Data\SystemPlan;
use hexa_package_publication_onboarding\Data\SystemReceipt;
use hexa_package_publication_onboarding\Data\SystemRollbackResult;
use hexa_package_publication_onboarding\Data\SystemVerification;

final class FakeDestinationPublicationPort implements DestinationPublicationPort
{
    /** @param ArrayObject<int, string> $events */
    public function __construct(private readonly ArrayObject $events)
    {
    }

    public function plan(OnboardingRequest $request): SystemPlan
    {
        $actions = [['id' => 'destination.configure_distributor']];
        if ($request->verificationRelease !== null) {
            $actions[] = ['id' => 'destination.force_sync'];
        }

        return new SystemPlan(
            'destination',
            $actions,
            ['configuration' => null],
            ['restore_configuration' => true],
        );
    }

    public function apply(OnboardingRequest $request, SystemPlan $plan, string $operationId): SystemReceipt
    {
        $this->events->append('destination.apply');

        return new SystemReceipt('destination', 'destination-receipt', ['configured' => true], ['configuration_revision' => 'before']);
    }

    public function verify(OnboardingRequest $request, ?SystemReceipt $receipt): SystemVerification
    {
        $this->events->append('destination.verify');

        return new SystemVerification('destination', true, [
            'plugin_basename' => 'hexa-pr-wire-distributor/hexa-pr-wire-distributor.php',
            'plugin_version' => '2.0.0',
            'plugin_active' => true,
            'echo_rss_required' => false,
            'fifu_required' => false,
            'images_hosted_on_source' => true,
            'deduplication_ready' => true,
            'configuration_fingerprint' => str_repeat('c', 64),
            'force_sync' => [
                'success' => true,
                'source_release_id' => 'release-123',
                'canonical_source_url' => 'https://hexaprwire.com/releases/release-123/',
                'destination_post_id' => 456,
                'destination_url' => 'https://example.com/release-123/',
                'deduplicated' => true,
                'structure_valid' => true,
                'category_valid' => true,
                'canonical_link_valid' => true,
                'images_hosted_on_source' => true,
                'image_urls' => ['https://hexaprwire.com/wp-content/uploads/release-123.jpg'],
            ],
        ]);
    }

    public function rollback(
        OnboardingRequest $request,
        SystemPlan $plan,
        SystemReceipt $receipt,
        string $operationId,
    ): SystemRollbackResult {
        $this->events->append('destination.rollback');

        return new SystemRollbackResult('destination', true, ['configuration_restored' => true]);
    }
}
