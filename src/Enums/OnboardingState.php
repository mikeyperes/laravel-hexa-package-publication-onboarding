<?php

namespace hexa_package_publication_onboarding\Enums;

enum OnboardingState: string
{
    case Planned = 'planned';
    case ApplyingSource = 'applying_source';
    case ApplyingDestination = 'applying_destination';
    case Applied = 'applied';
    case Verifying = 'verifying';
    case Verified = 'verified';
    case ConfiguredSyncPending = 'configured_sync_pending';
    case RollingBack = 'rolling_back';
    case RolledBack = 'rolled_back';
    case Failed = 'failed';
    case ReconciliationRequired = 'reconciliation_required';

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Verified,
            self::ConfiguredSyncPending,
            self::RolledBack,
        ], true);
    }

    public function isUncertain(): bool
    {
        return in_array($this, [
            self::ApplyingSource,
            self::ApplyingDestination,
            self::Verifying,
            self::RollingBack,
            self::ReconciliationRequired,
        ], true);
    }
}
