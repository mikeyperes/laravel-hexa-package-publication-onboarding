<?php

namespace hexa_package_publication_onboarding\Data;

use hexa_package_publication_onboarding\Support\SafeUrl;
use hexa_package_publication_onboarding\Support\SecretGuard;
use InvalidArgumentException;

final readonly class OnboardingRequest
{
    public string $sourceOrigin;

    /**
     * @param list<BrandingAsset> $brandingAssets
     * @param list<string> $requestedActions
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $runId,
        public OutletIdentity $outlet,
        public DestinationSite $destination,
        public ProtectedLoginReceipt $loginReceipt,
        public HierarchyPlacement $hierarchy,
        public array $brandingAssets,
        string $sourceOrigin,
        public string $sourceContractVersion,
        public string $hierarchyRevision,
        public PluginRequirement $distributorPlugin,
        public PressReleaseContract $pressReleaseContract,
        public array $requestedActions,
        public ?VerificationRelease $verificationRelease = null,
        public array $metadata = [],
    ) {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{7,127}$/', $this->runId) !== 1) {
            throw new InvalidArgumentException('A stable safe run ID between 8 and 128 characters is required.');
        }
        $this->sourceOrigin = SafeUrl::origin($sourceOrigin);
        if (trim($this->sourceContractVersion) === '' || trim($this->hierarchyRevision) === '') {
            throw new InvalidArgumentException('Source contract and hierarchy revision identifiers are required.');
        }
        if (! hash_equals($this->outlet->canonicalOrigin, $this->destination->canonicalOrigin)) {
            throw new InvalidArgumentException('The outlet and destination origins must match.');
        }
        if (! hash_equals($this->destination->installationId, $this->loginReceipt->installationId)) {
            throw new InvalidArgumentException('The Toolkit receipt is not bound to the destination installation.');
        }
        $roles = [];
        foreach ($this->brandingAssets as $asset) {
            if (! $asset instanceof BrandingAsset) {
                throw new InvalidArgumentException('Branding assets must use BrandingAsset values.');
            }
            $roles[] = $asset->role;
        }
        sort($roles);
        if ($roles !== ['icon', 'logo']) {
            throw new InvalidArgumentException('Exactly one logo and one icon are required.');
        }
        if ($this->requestedActions === [] || count($this->requestedActions) !== count(array_unique($this->requestedActions))) {
            throw new InvalidArgumentException('The ordered requested action IDs must be non-empty and unique.');
        }
        foreach ($this->requestedActions as $action) {
            if (preg_match('/^[a-z][a-z0-9._:-]{2,127}$/', (string) $action) !== 1) {
                throw new InvalidArgumentException('Requested action IDs must use safe machine-readable names.');
            }
        }
        SecretGuard::assertSafe($this->metadata);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'run_id' => $this->runId,
            'outlet' => $this->outlet->toArray(),
            'destination' => $this->destination->toArray(),
            'login_receipt' => $this->loginReceipt->toArray(),
            'hierarchy' => $this->hierarchy->toArray(),
            'branding_assets' => array_map(
                static fn (BrandingAsset $asset): array => $asset->toArray(),
                $this->brandingAssets,
            ),
            'source_origin' => $this->sourceOrigin,
            'source_contract_version' => $this->sourceContractVersion,
            'hierarchy_revision' => $this->hierarchyRevision,
            'distributor_plugin' => $this->distributorPlugin->toArray(),
            'press_release_contract' => $this->pressReleaseContract->toArray(),
            'requested_actions' => $this->requestedActions,
            'verification_release' => $this->verificationRelease?->toArray(),
            'metadata' => $this->metadata,
            'echo_rss_required' => false,
            'fifu_required' => false,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $assets = array_map(
            static fn (mixed $asset): BrandingAsset => BrandingAsset::fromArray(is_array($asset) ? $asset : []),
            is_array($data['branding_assets'] ?? null) ? $data['branding_assets'] : [],
        );

        return new self(
            (string) ($data['run_id'] ?? ''),
            OutletIdentity::fromArray(is_array($data['outlet'] ?? null) ? $data['outlet'] : []),
            DestinationSite::fromArray(is_array($data['destination'] ?? null) ? $data['destination'] : []),
            ProtectedLoginReceipt::fromArray(is_array($data['login_receipt'] ?? null) ? $data['login_receipt'] : []),
            HierarchyPlacement::fromArray(is_array($data['hierarchy'] ?? null) ? $data['hierarchy'] : []),
            $assets,
            (string) ($data['source_origin'] ?? ''),
            (string) ($data['source_contract_version'] ?? ''),
            (string) ($data['hierarchy_revision'] ?? ''),
            PluginRequirement::fromArray(is_array($data['distributor_plugin'] ?? null) ? $data['distributor_plugin'] : []),
            PressReleaseContract::fromArray(is_array($data['press_release_contract'] ?? null) ? $data['press_release_contract'] : []),
            array_values(array_map('strval', is_array($data['requested_actions'] ?? null) ? $data['requested_actions'] : [])),
            is_array($data['verification_release'] ?? null) ? VerificationRelease::fromArray($data['verification_release']) : null,
            is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }
}
