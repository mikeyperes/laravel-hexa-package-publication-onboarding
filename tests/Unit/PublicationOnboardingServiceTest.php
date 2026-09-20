<?php

namespace hexa_package_publication_onboarding\Tests\Unit;

use ArrayObject;
use hexa_package_publication_onboarding\Data\SystemPlan;
use hexa_package_publication_onboarding\Enums\OnboardingState;
use hexa_package_publication_onboarding\Orchestration\PublicationOnboardingService;
use hexa_package_publication_onboarding\Orchestration\VerificationPolicy;
use hexa_package_publication_onboarding\Tests\Support\FakeDestinationPublicationPort;
use hexa_package_publication_onboarding\Tests\Support\FakeSourcePublicationPort;
use hexa_package_publication_onboarding\Tests\Support\InMemoryOperationStore;
use hexa_package_publication_onboarding\Tests\Support\RequestFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PublicationOnboardingServiceTest extends TestCase
{
    public function test_plan_is_stable_and_apply_is_idempotent(): void
    {
        $events = new ArrayObject;
        $service = $this->service($events);
        $request = RequestFactory::make();

        $first = $service->plan($request);
        $second = $service->plan($request);

        self::assertSame($first->fingerprint, $second->fingerprint);
        $operation = $service->apply($first, 'approval-receipt-0001');
        self::assertSame(OnboardingState::Applied, $operation->state);
        self::assertSame($operation->operationId, $service->apply($first, 'approval-receipt-0001')->operationId);
        self::assertSame(['source.apply', 'destination.apply'], $events->getArrayCopy());

        $verified = $service->verify($first);
        self::assertSame(OnboardingState::Verified, $verified->state);
        self::assertFalse($verified->verifications['destination']->readback['echo_rss_required']);
        self::assertFalse($verified->verifications['destination']->readback['fifu_required']);
    }

    public function test_without_a_reviewed_release_verification_stays_sync_pending(): void
    {
        $events = new ArrayObject;
        $service = $this->service($events);
        $plan = $service->plan(RequestFactory::make(false));

        $service->apply($plan, 'approval-receipt-0002');
        $verified = $service->verify($plan);

        self::assertSame(OnboardingState::ConfiguredSyncPending, $verified->state);
    }

    public function test_rollback_runs_destination_before_source(): void
    {
        $events = new ArrayObject;
        $service = $this->service($events);
        $plan = $service->plan(RequestFactory::make());
        $service->apply($plan, 'approval-receipt-0003');

        $rolledBack = $service->rollback($plan);

        self::assertSame(OnboardingState::RolledBack, $rolledBack->state);
        self::assertSame(
            ['source.apply', 'destination.apply', 'destination.rollback', 'source.rollback'],
            $events->getArrayCopy(),
        );
    }

    public function test_secret_bearing_plan_fields_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SystemPlan(
            'source',
            [['id' => 'source.configure', 'password' => 'forbidden']],
            [],
            [],
        );
    }

    /** @param ArrayObject<int, string> $events */
    private function service(ArrayObject $events): PublicationOnboardingService
    {
        return new PublicationOnboardingService(
            new FakeSourcePublicationPort($events),
            new FakeDestinationPublicationPort($events),
            new InMemoryOperationStore,
            new VerificationPolicy,
            RequestFactory::policy(),
        );
    }
}
