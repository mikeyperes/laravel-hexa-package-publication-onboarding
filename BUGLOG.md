# Publication Onboarding Bug Log

Record critical or high production defects here with the symptom, impact,
root cause, durable patch and regression guard. No qualifying defects are
recorded for the initial contract-only implementation.

## POB-2026-001 — Valid feed queries blocked origin verification

- **Symptom:** Rich Reporter reconciliation reported that its source feed was
  missing or not hosted on the source origin even though source and destination
  verification both succeeded.
- **Impact:** Any publication feed using WordPress query parameters could remain
  in `reconciliation_required` after a successful onboarding operation.
- **Root cause:** `SafeUrl::sameOrigin()` reused the strict URL-construction
  parser, which rejected query and fragment components before comparing the
  HTTPS host and port.
- **Patch:** Origin comparison now permits query and fragment components on the
  candidate URL while retaining HTTPS, host, port, and credential checks.
  `origin()`, `wpAdmin()`, and `https()` remain strict.
- **Regression guard:** `tests/Unit/SafeUrlTest.php` covers WordPress feed
  queries, fragments, cross-origin rejection, credential rejection, and strict
  URL builders.
