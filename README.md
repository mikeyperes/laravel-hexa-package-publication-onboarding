# Laravel Hexa Publication Onboarding

Reusable, adapter-driven orchestration for onboarding one publication into
Hexa PR Wire distribution. The package owns immutable planning, idempotent
execution, cross-system receipts, verification, reconciliation and rollback.
It does not own WordPress credentials, plugin installation, HTTP endpoint
details, Publish models/controllers or WordPress plugin code.

## Ownership

- Hexa PR Wire Core owns source outlet records, hierarchy, source media and
  feed payloads.
- Hexa PR Wire Distributor owns destination polling/import, deduplication,
  remote source-image rendering and Force Sync.
- `hws-wp-toolkit` owns installation resolution, protected login and plugin
  installation/activation.
- `laravel-hexa-app-publish` binds this package's ports to the live systems and
  persists operation records.

The package never accepts passwords, usernames, cookies, tokens, OTP values or
secret-bearing URLs. It consumes a presence-only, positively validated Toolkit
receipt bound to the destination installation.

## Integration

Applications bind:

- `SourcePublicationPort`
- `DestinationPublicationPort`
- `OnboardingOperationStore`

Then resolve `PublicationOnboardingService` and call:

1. `preflight()` to inspect one outlet and return the current source hierarchy.
2. `plan()` to compile the immutable source/destination plan and fingerprints.
3. `apply()` once with the caller's reviewed approval-receipt ID.
4. `verify()` for normal post-apply readback.
5. `reconcile()` after any missing response or uncertain operation.
6. `rollback()` only from recorded receipts and rollback state.

The port adapters own transport. The domain engine never performs raw SQL,
WP-CLI composition, direct WordPress option writes or plugin installation.

## Required verification readback

Source verification must return:

- `outlet_id`
- `hierarchy_path`
- `feed_endpoint`
- `press_release_contract_version`
- `logo_url`
- `icon_url`
- `logo_attachment_id`
- `icon_attachment_id`

Both asset URLs must remain on the configured source origin.

Destination verification must return:

- `plugin_basename`
- `plugin_version`
- `plugin_active=true`
- `echo_rss_required=false`
- `fifu_required=false`
- `images_hosted_on_source=true`
- `deduplication_ready=true`
- `configuration_fingerprint`

When a reviewed existing release is included, `force_sync` must additionally
return `success`, the exact `source_release_id`, `canonical_source_url`,
`destination_post_id`, `destination_url`, source-hosted `image_urls`, structure,
category and canonical-link checks, and `deduplicated=true`.

Without a reviewed release the successful state is
`configured_sync_pending`, never fully `verified`.

## Idempotency and recovery

`run_id` binds to one plan fingerprint. A repeated apply returns an already
applied or terminal operation and never creates another outlet. A process that
stops while applying, verifying or rolling back becomes reconciliation work;
the same run cannot blindly retry. Rollback runs destination-first and then
source, touching only objects represented by recorded receipts.

## Deferred adapters

Core- and Distributor-specific endpoint adapters intentionally remain outside
this initial package release while the `hexa-pr-wire-core` refactor is active.
Bind them only after that plugin publishes its final versioned API and feed
contract. This package's ports are the stable boundary around that work.
