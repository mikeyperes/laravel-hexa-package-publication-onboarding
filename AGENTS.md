# Publication Onboarding Package

- Keep this package adapter-driven and independent of Publish Eloquent models,
  controllers, site IDs and WordPress endpoint details.
- Hexa PR Wire Core and Distributor are separate systems and separate plugins.
- Never accept or persist credential values; consume only safe Toolkit receipts.
- All logo, icon and release-image URLs remain hosted on `hexaprwire.com`.
- Echo RSS and FIFU are forbidden dependencies.
- Preserve plan fingerprints, operation receipts, idempotency and rollback
  behavior through every refactor.
- App-specific adapters and UI belong in `laravel-hexa-app-publish`.
