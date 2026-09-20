<?php

namespace hexa_package_publication_onboarding\Orchestration;

use hexa_package_publication_onboarding\Contracts\DestinationPublicationPort;
use hexa_package_publication_onboarding\Contracts\OnboardingOperationStore;
use hexa_package_publication_onboarding\Contracts\SourcePublicationPort;
use hexa_package_publication_onboarding\Data\BrandingAsset;
use hexa_package_publication_onboarding\Data\OnboardingOperation;
use hexa_package_publication_onboarding\Data\OnboardingPlan;
use hexa_package_publication_onboarding\Data\OnboardingRequest;
use hexa_package_publication_onboarding\Data\OutletIdentity;
use hexa_package_publication_onboarding\Data\PreflightResult;
use hexa_package_publication_onboarding\Enums\OnboardingState;
use hexa_package_publication_onboarding\Exceptions\OnboardingConflictException;
use hexa_package_publication_onboarding\Exceptions\OnboardingExecutionException;
use hexa_package_publication_onboarding\Support\SafeUrl;
use hexa_package_publication_onboarding\Support\SecretGuard;
use InvalidArgumentException;
use Throwable;

final class PublicationOnboardingService
{
    /** @param array<string, mixed> $policy */
    public function __construct(
        private readonly SourcePublicationPort $source,
        private readonly DestinationPublicationPort $destination,
        private readonly OnboardingOperationStore $operations,
        private readonly VerificationPolicy $verificationPolicy,
        private readonly array $policy = [],
    ) {
    }

    public function preflight(OutletIdentity $outlet): PreflightResult
    {
        $inspection = $this->source->inspect($outlet);
        SecretGuard::assertSafe($inspection);

        return new PreflightResult($inspection, $this->source->hierarchy());
    }

    public function plan(OnboardingRequest $request): OnboardingPlan
    {
        $this->assertPolicy($request);

        return OnboardingPlan::compile(
            $request,
            $this->source->plan($request),
            $this->destination->plan($request),
        );
    }

    public function apply(OnboardingPlan $plan, string $approvalReceiptId): OnboardingOperation
    {
        $this->assertPolicy($plan->request);
        $this->assertSafeReference($approvalReceiptId, 'approval receipt');
        $operation = $this->operations->find($plan->request->runId);
        if ($operation !== null) {
            $this->assertMatchingPlan($operation, $plan);
            if (! hash_equals($operation->approvalReceiptId, $approvalReceiptId)) {
                throw new OnboardingConflictException('The run ID is already bound to a different approval receipt.');
            }
            if (in_array($operation->state, [
                OnboardingState::Applied,
                OnboardingState::Verified,
                OnboardingState::ConfiguredSyncPending,
            ], true)) {
                return $operation;
            }
            if ($operation->state->isUncertain()) {
                throw new OnboardingConflictException('The existing run is uncertain and must be reconciled before another apply.');
            }
            if (in_array($operation->state, [OnboardingState::Failed, OnboardingState::RolledBack], true)) {
                throw new OnboardingConflictException('The existing terminal run ID cannot be applied again; create a new reviewed run.');
            }
        } else {
            $operation = OnboardingOperation::start($this->operationId($plan), $approvalReceiptId, $plan, $this->now());
            $this->operations->save($operation);
        }

        try {
            if (! isset($operation->receipts['source'])) {
                $operation = $operation->withState(OnboardingState::ApplyingSource, $this->now());
                $this->operations->save($operation);
                $receipt = $this->source->apply($plan->request, $plan->source, $operation->operationId);
                if ($receipt->system !== 'source') {
                    throw new InvalidArgumentException('The source port returned a destination receipt.');
                }
                $operation = $operation->withReceipt($receipt, OnboardingState::ApplyingDestination, $this->now());
                $this->operations->save($operation);
            }
            if (! isset($operation->receipts['destination'])) {
                $operation = $operation->withState(OnboardingState::ApplyingDestination, $this->now());
                $this->operations->save($operation);
                $receipt = $this->destination->apply($plan->request, $plan->destination, $operation->operationId);
                if ($receipt->system !== 'destination') {
                    throw new InvalidArgumentException('The destination port returned a source receipt.');
                }
                $operation = $operation->withReceipt($receipt, OnboardingState::Applied, $this->now());
                $this->operations->save($operation);
            }

            return $operation->state === OnboardingState::Applied
                ? $operation
                : $this->saveState($operation, OnboardingState::Applied);
        } catch (Throwable $throwable) {
            $operation = $operation->withError(
                SecretGuard::exceptionMessage($throwable),
                OnboardingState::ReconciliationRequired,
                $this->now(),
            );
            $this->operations->save($operation);

            throw new OnboardingExecutionException($operation->error, $operation, $throwable);
        }
    }

    public function verify(OnboardingPlan $plan): OnboardingOperation
    {
        $operation = $this->requiredOperation($plan);
        if (in_array($operation->state, [OnboardingState::Verified, OnboardingState::ConfiguredSyncPending], true)) {
            return $operation;
        }
        if ($operation->state !== OnboardingState::Applied) {
            throw new OnboardingConflictException('Only an applied run can enter normal verification; uncertain runs must reconcile.');
        }

        return $this->verifyCurrentState($operation);
    }

    public function reconcile(OnboardingPlan $plan): OnboardingOperation
    {
        $operation = $this->requiredOperation($plan);
        if ($operation->state->isTerminal()) {
            return $operation;
        }

        return $this->verifyCurrentState($operation);
    }

    public function rollback(OnboardingPlan $plan): OnboardingOperation
    {
        $operation = $this->requiredOperation($plan);
        if ($operation->state === OnboardingState::RolledBack) {
            return $operation;
        }
        if ($operation->receipts === []) {
            throw new OnboardingConflictException('No confirmed system receipt exists; reconcile before attempting rollback.');
        }

        $operation = $this->saveState($operation, OnboardingState::RollingBack);
        try {
            if (isset($operation->receipts['destination']) && ! isset($operation->rollbacks['destination'])) {
                $rollback = $this->destination->rollback(
                    $plan->request,
                    $plan->destination,
                    $operation->receipts['destination'],
                    $operation->operationId,
                );
                if ($rollback->system !== 'destination') {
                    throw new InvalidArgumentException('The destination port returned a source rollback result.');
                }
                $operation = $operation->withRollback($rollback, $this->now());
                $this->operations->save($operation);
            }
            if (isset($operation->receipts['source']) && ! isset($operation->rollbacks['source'])) {
                $rollback = $this->source->rollback(
                    $plan->request,
                    $plan->source,
                    $operation->receipts['source'],
                    $operation->operationId,
                );
                if ($rollback->system !== 'source') {
                    throw new InvalidArgumentException('The source port returned a destination rollback result.');
                }
                $operation = $operation->withRollback($rollback, $this->now());
                $this->operations->save($operation);
            }

            $failed = array_filter(
                $operation->rollbacks,
                static fn ($rollback): bool => ! $rollback->success,
            );
            $state = $failed === [] ? OnboardingState::RolledBack : OnboardingState::ReconciliationRequired;
            $error = $failed === [] ? '' : implode(' ', array_map(
                static fn ($rollback): string => $rollback->blocker,
                $failed,
            ));
            $operation = $error === ''
                ? $operation->withState($state, $this->now())
                : $operation->withError($error, $state, $this->now());
            $this->operations->save($operation);

            return $operation;
        } catch (Throwable $throwable) {
            $operation = $operation->withError(
                SecretGuard::exceptionMessage($throwable),
                OnboardingState::ReconciliationRequired,
                $this->now(),
            );
            $this->operations->save($operation);

            throw new OnboardingExecutionException($operation->error, $operation, $throwable);
        }
    }

    private function verifyCurrentState(OnboardingOperation $operation): OnboardingOperation
    {
        $operation = $this->saveState($operation, OnboardingState::Verifying);
        try {
            $source = $this->source->verify($operation->plan->request, $operation->receipts['source'] ?? null);
            if ($source->system !== 'source') {
                throw new InvalidArgumentException('The source port returned a destination verification.');
            }
            $operation = $operation->withVerification($source, $this->now());
            $this->operations->save($operation);

            $destination = $this->destination->verify($operation->plan->request, $operation->receipts['destination'] ?? null);
            if ($destination->system !== 'destination') {
                throw new InvalidArgumentException('The destination port returned a source verification.');
            }
            $operation = $operation->withVerification($destination, $this->now());
            $this->operations->save($operation);

            $blockers = $this->verificationPolicy->blockers($operation->plan->request, $source, $destination);
            if ($blockers !== []) {
                $operation = $operation->withError(
                    implode(' ', $blockers),
                    OnboardingState::ReconciliationRequired,
                    $this->now(),
                );
            } else {
                $state = $operation->plan->request->verificationRelease === null
                    ? OnboardingState::ConfiguredSyncPending
                    : OnboardingState::Verified;
                $operation = $operation->withError('', $state, $this->now());
            }
            $this->operations->save($operation);

            return $operation;
        } catch (Throwable $throwable) {
            $operation = $operation->withError(
                SecretGuard::exceptionMessage($throwable),
                OnboardingState::ReconciliationRequired,
                $this->now(),
            );
            $this->operations->save($operation);

            throw new OnboardingExecutionException($operation->error, $operation, $throwable);
        }
    }

    private function requiredOperation(OnboardingPlan $plan): OnboardingOperation
    {
        $operation = $this->operations->find($plan->request->runId);
        if ($operation === null) {
            throw new OnboardingConflictException('No persisted operation exists for this run ID.');
        }
        $this->assertMatchingPlan($operation, $plan);

        return $operation;
    }

    private function assertMatchingPlan(OnboardingOperation $operation, OnboardingPlan $plan): void
    {
        if (! hash_equals($operation->plan->fingerprint, $plan->fingerprint)) {
            throw new OnboardingConflictException('The run ID is already bound to a different immutable plan.');
        }
    }

    private function assertPolicy(OnboardingRequest $request): void
    {
        $sourceOrigin = (string) ($this->policy['source_origin'] ?? 'https://hexaprwire.com');
        if (! hash_equals(SafeUrl::origin($sourceOrigin), $request->sourceOrigin)) {
            throw new InvalidArgumentException('The request source origin does not match package policy.');
        }
        $plugin = (string) ($this->policy['distributor_plugin'] ?? 'hexa-pr-wire-distributor/hexa-pr-wire-distributor.php');
        if (! hash_equals($plugin, $request->distributorPlugin->basename)) {
            throw new InvalidArgumentException('The request Distributor plugin does not match package policy.');
        }
        $contract = (string) ($this->policy['press_release_contract_version'] ?? '1.0');
        if (! hash_equals($contract, $request->pressReleaseContract->version)) {
            throw new InvalidArgumentException('The press-release contract version does not match package policy.');
        }

        $allowedRoles = $this->policy['allowed_branding_roles'] ?? ['logo', 'icon'];
        $allowedMimes = $this->policy['allowed_branding_mime_types'] ?? [];
        foreach ($request->brandingAssets as $asset) {
            if (! $asset instanceof BrandingAsset || ! in_array($asset->role, $allowedRoles, true)) {
                throw new InvalidArgumentException('The branding asset role is not allowed by package policy.');
            }
            if ($allowedMimes !== [] && ! in_array(strtolower($asset->mimeType), $allowedMimes, true)) {
                throw new InvalidArgumentException('The branding asset MIME type is not allowed by package policy.');
            }
        }

        if ($request->verificationRelease !== null
            && ! SafeUrl::sameOrigin($request->verificationRelease->canonicalUrl, $request->sourceOrigin)) {
            throw new InvalidArgumentException('The verification release must be hosted on the source origin.');
        }
    }

    private function saveState(OnboardingOperation $operation, OnboardingState $state): OnboardingOperation
    {
        $operation = $operation->withState($state, $this->now());
        $this->operations->save($operation);

        return $operation;
    }

    private function operationId(OnboardingPlan $plan): string
    {
        return 'publication-onboarding-'.substr(hash('sha256', $plan->request->runId.':'.$plan->fingerprint), 0, 24);
    }

    private function assertSafeReference(string $value, string $label): void
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{7,255}$/', $value) !== 1) {
            throw new InvalidArgumentException("A safe {$label} identifier is required.");
        }
    }

    private function now(): string
    {
        return gmdate('c');
    }
}
