# CT2 Non-Functional Evidence

## Purpose
This document records the direct non-functional evidence that currently exists in the repository for the integrated `CORE TRANSACTION 2: Back-Office and Agency Management System`. It is intentionally narrower than a formal certification pack: it captures what CT2 can actually prove today, the repeatable checks that produce that evidence, and the remaining gaps that are still technical debt rather than hidden assumptions.

## Evidence Sources
- `bash ct2_back_office/scripts/ct2_lint.sh`
- `php ct2_back_office/scripts/ct2_smoke_check.php`
- `php ct2_back_office/scripts/ct2_db_smoke_check.php`
- `bash ct2_back_office/scripts/ct2_runtime_hardening_check.sh`
- `docs/ct2_manual_qa_pack.md`
- `docs/ct2_api_validation.md`
- `docs/ct2_qa_execution_report.md`
- `docs/ct2_deployment_guide.md`
- `docs/ct2_operator_runbook.md`

## Directly Proven
### Security controls
- Session-backed authentication and role-based route/API restrictions are exercised through the DB smoke check, manual QA pack, and runtime hardening script.
- Invalid CSRF tokens are now directly proven to be rejected for representative protected browser writes in approvals, supplier onboarding, and visa checklist verification.
- Stale-session write attempts are directly proven to be redirected to the login flow without persisting an update.
- Protected API failures are directly proven to stay JSON-shaped on representative `403` and `405` paths.

### Audit logging
- Representative write paths now have direct repeatable audit evidence:
  `agents.update`,
  `suppliers.onboarding_update`,
  `approvals.decide`,
  `visa.document_checklist_update`,
  `financial.flag_update`.
- Negative security paths are also checked to confirm invalid-CSRF and stale-session writes do not create audit entries for the attempted action.

### Runtime quality on exercised flows
- The current lint, structural smoke, DB smoke, and runtime hardening checks run without PHP warnings, notices, or fatal errors on the exercised CT2 paths.
- The runtime hardening script covers:
  admin sign-in,
  dashboard load,
  availability search/read path,
  agent update,
  supplier onboarding negative and positive paths,
  approval negative and positive paths,
  visa checklist negative and positive upload paths,
  financial reconciliation update,
  financial CSV export,
  logout,
  stale-session browser rejection,
  JSON-only protected API failures.

### Cross-platform runtime contract
- The application still uses the same TCP-first MySQL configuration contract for LAMP and Windows XAMPP:
  `host`, `port`, `name`, `username`, `password`, `charset`.
- Session and upload storage remain relative to `ct2_back_office/storage/`, which keeps the runtime contract portable between Linux and Windows file layouts.

## Partially Proven
### Browser and module breadth
- All major modules are implemented and route-validated, but the scripted regression coverage is still representative rather than exhaustive for every create/update path.
- Supplier contracts, supplier KPI creation, supplier notes, availability creation flows, marketing mutation flows, and some visa/financial auxiliary forms still rely more heavily on manual QA than scripted hardening.

### Warning-free runtime scope
- Warning-free execution is directly proven for the exercised hardening and QA paths.
- It is not yet proven by automation for every reachable route/action pair in CT2.

### Accessibility and performance
- The repo now has explicit NFR evidence documentation, but it still does not contain formal accessibility audits, keyboard-navigation audits, performance benchmarks, or load-test artifacts.
- Current confidence on accessibility and performance remains based on code inspection and general UI usability during manual QA, not on dedicated measurement.

## Remaining Gaps
- The original CT2 diagrams and requirements pack are still missing from `docs/diagrams/`, so strict upstream traceability cannot yet be proven.
- Performance, accessibility, and load behavior remain validation gaps.
- Cross-platform compatibility is documented and code-aligned, but runtime evidence in-repo is still strongest on the local Linux LAMP environment rather than an executed Windows XAMPP run.

## Current Recommendation
- Treat CT2 security, audit, and runtime-hardening evidence as materially stronger than the earlier release-only state.
- Treat formal accessibility/performance assurance and upstream-diagram conformance as the main remaining non-functional debt.
