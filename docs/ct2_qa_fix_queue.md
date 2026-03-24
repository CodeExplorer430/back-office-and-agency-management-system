# CT2 QA Fix Queue

## Historical Snapshot
- This queue records the March 10, 2026 QA blockers that were found and cleared during that release cycle.
- Current blocking policy for new defects is defined by `docs/ct2_quality_gate.md`.

## Resolved On March 10, 2026

### Search and filter flows crash with `SQLSTATE[HY093]`
- Status: Resolved on `develop`
- Resolution:
  list queries now build unique placeholders for multi-column `LIKE` filters through the shared model layer, removing the invalid-parameter failure across agents, staff, suppliers, availability, marketing, promotions, affiliates, and visa applications.
- Verification:
  browser and API reruns for all previously failing search paths returned `200` with the expected seeded matches and no application error or HTML fatal output.

### API fatal output bypasses the JSON error contract when server-side exceptions occur
- Status: Resolved on `develop`
- Resolution:
  API bootstrap now registers centralized exception handling and returns the CT2 JSON error envelope with `500` status instead of leaking raw fatal output.
- Verification:
  filtered API reruns stayed on the JSON contract, and the existing `403` and `405` negative cases remained unchanged after the runtime hardening.

### Expand regression coverage for search-enabled lists after the blocker fix
- Status: Resolved on `develop`
- Resolution:
  `ct2_db_smoke_check.php` now executes seeded DB-backed search checks for agents, staff, suppliers, resources, campaigns, promotions, affiliates, and visa applications.
- Verification:
  the updated DB smoke check passed against the live local MySQL environment after the fix.

## Open Items
- No open blocker remains from this QA milestone.
- New warnings, notices, deprecations, runtime hardening failures, or suite regressions are treated as immediate blockers under the current strict gate.
