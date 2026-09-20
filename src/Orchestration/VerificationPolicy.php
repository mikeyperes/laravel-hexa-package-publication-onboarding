<?php

namespace hexa_package_publication_onboarding\Orchestration;

use hexa_package_publication_onboarding\Data\OnboardingRequest;
use hexa_package_publication_onboarding\Data\SystemVerification;
use hexa_package_publication_onboarding\Support\SafeUrl;

final class VerificationPolicy
{
    /** @return list<string> */
    public function blockers(
        OnboardingRequest $request,
        SystemVerification $source,
        SystemVerification $destination,
    ): array {
        $blockers = [];
        if (! $source->success) {
            $blockers[] = 'Source verification failed: '.$source->blocker;
        }
        if (! $destination->success) {
            $blockers[] = 'Destination verification failed: '.$destination->blocker;
        }
        if ($blockers !== []) {
            return $blockers;
        }

        $this->requireValue($blockers, $source->readback, 'outlet_id', 'Source outlet ID is missing.');
        if (($source->readback['hierarchy_path'] ?? null) !== $request->hierarchy->parentPath) {
            $blockers[] = 'Source hierarchy readback does not match the reviewed placement.';
        }
        $feedEndpoint = (string) ($source->readback['feed_endpoint'] ?? '');
        if ($feedEndpoint === '' || ! $this->sameOrigin($feedEndpoint, $request->sourceOrigin)) {
            $blockers[] = 'Source feed endpoint is missing or not hosted on the source origin.';
        }
        if (($source->readback['source_contract_version'] ?? null) !== $request->sourceContractVersion) {
            $blockers[] = 'Source API contract readback does not match the reviewed version.';
        }
        if (($source->readback['hierarchy_revision'] ?? null) !== $request->hierarchyRevision) {
            $blockers[] = 'Source hierarchy revision changed after review.';
        }
        if (($source->readback['press_release_contract_version'] ?? null) !== $request->pressReleaseContract->version) {
            $blockers[] = 'Press-release contract readback does not match the reviewed version.';
        }

        foreach (['logo_url', 'icon_url'] as $field) {
            $url = (string) ($source->readback[$field] ?? '');
            if ($url === '' || ! $this->sameOrigin($url, $request->sourceOrigin)) {
                $blockers[] = "{$field} is not hosted on the source origin.";
            }
        }
        $this->requireValue($blockers, $source->readback, 'logo_attachment_id', 'Source logo attachment ID is missing.');
        $this->requireValue($blockers, $source->readback, 'icon_attachment_id', 'Source icon attachment ID is missing.');

        if (($destination->readback['plugin_basename'] ?? null) !== $request->distributorPlugin->basename) {
            $blockers[] = 'The active Distributor plugin does not match the reviewed basename.';
        }
        if (($destination->readback['plugin_version'] ?? null) !== $request->distributorPlugin->version) {
            $blockers[] = 'The active Distributor version does not match the reviewed version.';
        }
        if (($destination->readback['plugin_active'] ?? false) !== true) {
            $blockers[] = 'The Distributor plugin is not confirmed active.';
        }
        if (($destination->readback['echo_rss_required'] ?? null) !== false) {
            $blockers[] = 'Echo RSS independence is not proven.';
        }
        if (($destination->readback['fifu_required'] ?? null) !== false) {
            $blockers[] = 'FIFU independence is not proven.';
        }
        if (($destination->readback['images_hosted_on_source'] ?? false) !== true) {
            $blockers[] = 'Source-hosted image behavior is not proven.';
        }
        if (($destination->readback['deduplication_ready'] ?? false) !== true) {
            $blockers[] = 'Destination deduplication readiness is not proven.';
        }
        $this->requireValue($blockers, $destination->readback, 'configuration_fingerprint', 'Destination configuration fingerprint is missing.');

        if ($request->verificationRelease !== null) {
            $sync = is_array($destination->readback['force_sync'] ?? null)
                ? $destination->readback['force_sync']
                : [];
            if (($sync['success'] ?? false) !== true) {
                $blockers[] = 'Force Sync did not complete successfully.';
            }
            if ((string) ($sync['source_release_id'] ?? '') !== $request->verificationRelease->sourceReleaseId) {
                $blockers[] = 'Force Sync did not use the reviewed source release.';
            }
            if (($sync['canonical_source_url'] ?? null) !== $request->verificationRelease->canonicalUrl) {
                $blockers[] = 'Force Sync canonical source URL does not match the reviewed release.';
            }
            $this->requireValue($blockers, $sync, 'destination_post_id', 'Force Sync destination post ID is missing.');
            $this->requireValue($blockers, $sync, 'destination_url', 'Force Sync destination URL is missing.');
            if (isset($sync['destination_url']) && ! $this->sameOrigin((string) $sync['destination_url'], $request->destination->canonicalOrigin)) {
                $blockers[] = 'Force Sync destination URL does not use the reviewed destination origin.';
            }
            if (($sync['deduplicated'] ?? false) !== true) {
                $blockers[] = 'Force Sync deduplication readback is missing.';
            }
            foreach (['structure_valid', 'category_valid', 'canonical_link_valid', 'images_hosted_on_source'] as $field) {
                if (($sync[$field] ?? false) !== true) {
                    $blockers[] = "Force Sync {$field} readback is not proven.";
                }
            }
            $imageUrls = is_array($sync['image_urls'] ?? null) ? $sync['image_urls'] : [];
            if ($imageUrls === []) {
                $blockers[] = 'Force Sync image URL readback is missing.';
            }
            foreach ($imageUrls as $imageUrl) {
                if (! is_string($imageUrl) || ! $this->sameOrigin($imageUrl, $request->sourceOrigin)) {
                    $blockers[] = 'A Force Sync image is not hosted on the source origin.';
                    break;
                }
            }
        }

        return $blockers;
    }

    /**
     * @param list<string> $blockers
     * @param array<string, mixed> $values
     */
    private function requireValue(array &$blockers, array $values, string $key, string $message): void
    {
        if (! isset($values[$key]) || $values[$key] === '' || $values[$key] === null) {
            $blockers[] = $message;
        }
    }

    private function sameOrigin(string $url, string $origin): bool
    {
        try {
            return SafeUrl::sameOrigin($url, $origin);
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
