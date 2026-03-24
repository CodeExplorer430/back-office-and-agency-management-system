# CT2 Integration Guide

## Purpose
Use this guide when integrating CT2 into its surrounding environment: cPanel hosting, GitHub release automation, and upstream or downstream systems such as CT1, finance, customer, or supplier source systems.

## Integration Model
CT2 is not an isolated application. It operates as a back-office layer that references upstream operational records while owning only its internal workflow, approval, staffing, availability, marketing, visa, and reporting state.

## Hosting And Deployment Integration
### GitHub to cPanel path
1. GitHub Actions runs the strict suite.
2. A validated artifact is built with `ct2_release_artifact.sh`.
3. The artifact is deployed to cPanel over SSH.
4. Shared config and writable storage are relinked.
5. Pre-live and live health checks complete before the release is accepted.

### Required cPanel integration points
- SSH access for automated deployment
- Shared config path for `ct2_local.php`
- Shared storage paths for sessions and uploads
- Stable base URL for `CT2_BASE_URL`
- MySQL reachable from the cPanel PHP runtime

## External System Boundaries
### CT2 owns
- agent and staff operating records inside CT2
- supplier onboarding, KPI, and relationship notes
- availability allocations and dispatch workflow state
- marketing campaign workflow state
- visa case workflow state
- approval records, audit logs, and reporting definitions

### External systems own
- core booking origin data
- customer master data
- external supplier master systems, if any
- external payment systems and finance ledgers
- external identity systems

### Integration rule
When CT2 stores external IDs such as booking, customer, supplier, or payment references, CT2 is storing a linkage pointer, not taking ownership of the source record.

## Data And API Integration
- JSON endpoints live under `ct2_back_office/api/`.
- API responses must stay JSON-only with no HTML or PHP warning leakage.
- Protected endpoints require an authenticated user and the permissions enforced by the endpoint. `api.access` is the common baseline for the current API surface, but module-specific write authorization should still mirror the browser-side permission model.
- Session-based browser authentication remains the default application contract.
- Any external caller or connector should assume CT2 is enforcing role and permission checks, not anonymous public API access.

## Operational Integration Considerations
- Uploads written by visa and related flows depend on persistent writable storage.
- CSV exports depend on correct response headers and browser/server behavior.
- Validation and release automation depend on the seeded schema baseline from `ct2_setup.sql`.
- Browser-heavy quality gates belong in GitHub CI, while cPanel should run the lighter post-deploy verification set.

## Common Integration Scenarios
### Integrating CT2 with CT1 or booking systems
- pass reference IDs into CT2 fields
- do not overwrite CT2 workflow state with raw source-state updates without an explicit mapping rule
- treat reconciliation issues as cross-system investigations, not CT2-only defects

### Integrating CT2 with finance exports
- use CT2 export endpoints and report runs as operational reporting outputs
- do not treat CT2 CSV exports as the authoritative finance ledger

### Integrating CT2 with cPanel hosting
- preserve the shared config/storage contract
- deploy artifacts, not unvalidated branch checkouts
- require post-deploy verification before handing the environment back to users

## Related Guides
- Setup and first launch: `docs/ct2_setup_guide.md`
- Deployment execution: `docs/ct2_deployment_guide.md`
- cPanel release contract: `docs/ct2_cpanel_release_flow.md`
- API validation checklist: `docs/ct2_api_validation.md`
- Troubleshooting: `docs/ct2_troubleshooting_guide.md`
