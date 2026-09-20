<?php

namespace hexa_package_publication_onboarding\Tests\Support;

use hexa_package_publication_onboarding\Data\BrandingAsset;
use hexa_package_publication_onboarding\Data\DestinationSite;
use hexa_package_publication_onboarding\Data\HierarchyPlacement;
use hexa_package_publication_onboarding\Data\OnboardingRequest;
use hexa_package_publication_onboarding\Data\OutletIdentity;
use hexa_package_publication_onboarding\Data\PluginRequirement;
use hexa_package_publication_onboarding\Data\PressReleaseContract;
use hexa_package_publication_onboarding\Data\ProtectedLoginReceipt;
use hexa_package_publication_onboarding\Data\VerificationRelease;

final class RequestFactory
{
    public static function make(bool $withVerificationRelease = true): OnboardingRequest
    {
        return new OnboardingRequest(
            'run-example-0001',
            new OutletIdentity('Example Publication', 'example-publication', 'https://example.com'),
            new DestinationSite('https://example.com', 'https://example.com/wp-admin/', 'install-17', 'account-2', 'site-8'),
            new ProtectedLoginReceipt('toolkit-receipt-1', 'install-17', 'valid', '2026-09-20T00:00:00+00:00'),
            HierarchyPlacement::under(10, 'Hexa'),
            [
                new BrandingAsset('logo', 'upload-logo-1', 'logo.png', 'image/png', str_repeat('a', 64), 800, 240),
                new BrandingAsset('icon', 'upload-icon-1', 'icon.png', 'image/png', str_repeat('b', 64), 512, 512),
            ],
            'https://hexaprwire.com',
            'core-api-1.0',
            'hierarchy-revision-1',
            new PluginRequirement('hexa-pr-wire-distributor/hexa-pr-wire-distributor.php', '2.0.0'),
            new PressReleaseContract('1.0', [
                'headline',
                'dateline',
                'lead',
                'body',
                'boilerplate',
                'media_contact',
                'canonical_url',
                'published_at',
                'image',
            ]),
            array_values(array_filter([
                'source.upload_branding',
                'source.upsert_outlet',
                'destination.configure_distributor',
                $withVerificationRelease ? 'destination.force_sync' : null,
            ])),
            $withVerificationRelease
                ? new VerificationRelease('release-123', 'https://hexaprwire.com/releases/release-123/')
                : null,
        );
    }

    /** @return array<string, mixed> */
    public static function policy(): array
    {
        return [
            'source_origin' => 'https://hexaprwire.com',
            'distributor_plugin' => 'hexa-pr-wire-distributor/hexa-pr-wire-distributor.php',
            'press_release_contract_version' => '1.0',
            'allowed_branding_roles' => ['logo', 'icon'],
            'allowed_branding_mime_types' => ['image/png'],
        ];
    }
}
