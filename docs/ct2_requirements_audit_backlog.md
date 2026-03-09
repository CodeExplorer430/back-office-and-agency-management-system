# CT2 Requirements Audit Backlog

## Purpose
This backlog captures the open actions surfaced by `docs/ct2_requirements_traceability_matrix.md`. It prioritizes gaps that block strict requirements closure ahead of lower-risk evidence improvements.

## Priority 1
### Import the missing CT2 diagrams and requirements pack
- Type: Source-of-truth gap
- Impact: The repo cannot currently prove strict conformance to the original CT2 functional and non-functional requirements because `docs/diagrams/` contains only an organizational chart.
- Expected work:
  collect the actual CT2 workflow, process, use-case, and requirements diagrams from the documentation teams and add them under `docs/diagrams/`.
- Acceptance criteria:
  the repo contains the real CT2 diagrams pack,
  the traceability matrix gains an upstream-diagram source column,
  and every matrix row is updated from `Source Gap` or repo-derived wording to explicit upstream mapping.

## Priority 2
### Add formal performance and accessibility evidence
- Type: Validation gap
- Impact: Security, audit, and representative runtime hardening now have direct evidence, but performance and accessibility still rely on code inspection and manual usability impressions.
- Expected work:
  define lightweight but real accessibility checks such as keyboard navigation and heading/form-label review;
  add a reproducible performance sanity check for the seeded local environment;
  record those results in `docs/ct2_nfr_evidence.md`.
- Acceptance criteria:
  the audit matrix can upgrade performance/accessibility from `Partially Implemented` to `Implemented`,
  and the repo contains a repeatable method to demonstrate those NFRs.

## Priority 3
### Broaden automated regression coverage beyond current smoke checks
- Type: Validation gap
- Impact: Current confidence is materially stronger after the runtime hardening script, but several lower-frequency mutation paths still depend more on manual QA than scripted protection.
- Expected work:
  extend the DB-backed smoke layer and runtime hardening checks to cover more write paths, especially marketing mutations, availability creation flows, supplier contracts/KPIs/notes, and additional visa/financial auxiliary forms.
- Acceptance criteria:
  representative auth, approval, upload, export, and cross-module read/write paths are covered by repeatable scripted checks,
  and fewer functional rows remain `Partially Implemented` only because of evidence gaps.

## Priority 4
### Add executed Windows XAMPP runtime evidence
- Type: Validation gap
- Impact: The CT2 runtime contract is cross-platform by design, but in-repo runtime proof is still strongest on the local Linux LAMP environment.
- Expected work:
  execute the deployment guide, DB smoke, runtime hardening script, and a short browser/API pass on a Windows XAMPP setup;
  record the result in the deployment or NFR docs.
- Acceptance criteria:
  the repo contains explicit Windows XAMPP execution evidence,
  and cross-platform compatibility is no longer only code-aligned and documented.

## Priority 5
### Add a broader warning-free route sweep
- Type: Validation gap
- Impact: Normal validated flows are now proven clean, but the repo still lacks an automated sweep across all reachable CT2 routes and common filter combinations.
- Expected work:
  add a lightweight route crawler or scripted route matrix that loads every module route, key filter variant, and a broader set of export/API entrypoints under `E_ALL`.
- Acceptance criteria:
  warning-free runtime evidence extends beyond the current representative hardening flows,
  and the route sweep becomes part of the repeatable local validation toolset.

## Current Recommendation
- Treat CT2 as strongly hardened for the repo-defined scope.
- Treat the remaining debt as traceability and validation-depth work, not obvious missing core module implementation.
