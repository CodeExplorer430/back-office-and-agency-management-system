# CT2 Non-Functional Evidence

## Purpose
This document records the direct non-functional evidence that currently exists in the repository for the integrated `CORE TRANSACTION 2: Back-Office and Agency Management System`. It is intentionally narrower than a formal certification pack: it captures what CT2 can actually prove today, the repeatable checks that produce that evidence, and the remaining gaps that are still technical debt rather than hidden assumptions.

## Evidence Sources
- `bash ct2_back_office/scripts/ct2_lint.sh`
- `php ct2_back_office/scripts/ct2_smoke_check.php`
- `php ct2_back_office/scripts/ct2_db_smoke_check.php`
- `bash ct2_back_office/scripts/ct2_route_matrix_check.sh`
- `bash ct2_back_office/scripts/ct2_runtime_hardening_check.sh`
- `docs/ct2_manual_qa_pack.md`
- `docs/ct2_api_validation.md`
- `docs/ct2_qa_execution_report.md`
- `docs/ct2_deployment_guide.md`
- `docs/ct2_operator_runbook.md`

## Directly Proven
### Security controls
- Session-backed authentication and role-based route/API restrictions are exercised through the DB smoke check, manual QA pack, and runtime hardening script.
- Invalid CSRF tokens are now directly proven to be rejected for representative protected browser writes in approvals, supplier onboarding, availability resource creation, marketing campaign save, visa checklist verification, and financial report generation.
- Stale-session write attempts are directly proven to be redirected to the login flow without persisting an update on both agent and financial write paths.
- Protected API failures are directly proven to stay JSON-shaped on representative `403` and `405` paths.

### Audit logging
- Representative write paths now have direct repeatable audit evidence:
  `agents.update`,
  `suppliers.onboarding_update`,
  `suppliers.contract_create`,
  `suppliers.kpi_create`,
  `suppliers.note_create`,
  `approvals.decide`,
  `availability.resource_create`,
  `availability.package_create`,
  `availability.allocation_create`,
  `availability.block_create`,
  `availability.vehicle_create`,
  `availability.driver_create`,
  `availability.dispatch_create`,
  `availability.maintenance_create`,
  `marketing.campaign_update`,
  `marketing.promotion_update`,
  `marketing.voucher_create`,
  `marketing.affiliate_update`,
  `marketing.referral_create`,
  `marketing.redemption_create`,
  `marketing.metric_create`,
  `marketing.note_create`,
  `visa.document_checklist_update`,
  `visa.payment_create`,
  `visa.notification_create`,
  `visa.note_create`,
  `financial.filter_create`,
  `financial.run_generate`,
  `financial.flag_update`.
- Negative security paths are also checked to confirm invalid-CSRF and stale-session writes do not create audit entries for the attempted action.

### Runtime quality on exercised flows
- The current lint, structural smoke, DB smoke, and runtime hardening checks run without PHP warnings, notices, or fatal errors on the exercised CT2 paths.
- The runtime hardening script now covers:
  admin sign-in,
  dashboard load,
  availability search/read path,
  agent update,
  supplier onboarding, contract, KPI, and relationship-note paths,
  approval negative and positive paths,
  availability resource, package, allocation, block, vehicle, driver, dispatch, and maintenance paths,
  marketing campaign, promotion, voucher, affiliate, referral, redemption, metric, and note paths,
  visa checklist negative and positive upload paths plus payment, notification, and note paths,
  financial filter creation,
  financial run generation,
  financial reconciliation update,
  financial CSV export,
  logout,
  stale-session browser rejection on multiple protected writes,
  JSON-only protected API failures.
- The route matrix script adds breadth coverage for:
  all primary module index routes,
  seeded search/filter variants,
  representative JSON GET entrypoints,
  and financial CSV export headers/body shape.

### Cross-platform runtime contract
- The application still uses the same TCP-first MySQL configuration contract for LAMP and Windows XAMPP:
  `host`, `port`, `name`, `username`, `password`, `charset`.
- Session and upload storage remain relative to `ct2_back_office/storage/`, which keeps the runtime contract portable between Linux and Windows file layouts.

## Partially Proven
### Browser and module breadth
- All primary CT2 module routes, seeded filter variants, representative JSON GET endpoints, and major mutation flows are now script-covered.
- Remaining manual-heavy coverage is concentrated in role-specific UAT sequencing, some less-used API POST mutation paths, and operator judgment around workflow usability rather than basic persistence or route health.

### Warning-free runtime scope
- Warning-free execution is directly proven for the route matrix, the expanded hardening flows, DB smoke, and the previously executed manual QA scenarios.
- It is still not claimed as exhaustive for every reachable route/action pair or arbitrary parameter combination in CT2.

### Accessibility and performance
- The repo now has explicit NFR evidence documentation, but it still does not contain formal accessibility audits, keyboard-navigation audits, performance benchmarks, or load-test artifacts.
- Current confidence on accessibility and performance remains based on code inspection and general UI usability during manual QA, not on dedicated measurement.

## Remaining Gaps
- Performance, accessibility, and load behavior remain validation gaps.
- Cross-platform compatibility is documented and code-aligned, but runtime evidence in-repo is still strongest on the local Linux LAMP environment rather than an executed Windows XAMPP run.

## Current Recommendation
- Treat CT2 security, audit, route breadth, and runtime-hardening evidence as materially stronger than the earlier release-only state.
- Treat formal accessibility/performance assurance and executed Windows XAMPP evidence as the main remaining repo-owned non-functional debt.
