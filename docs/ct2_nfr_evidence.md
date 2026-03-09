# CT2 Non-Functional Evidence

## Purpose
This document records the direct non-functional evidence that currently exists in the repository for the integrated `CORE TRANSACTION 2: Back-Office and Agency Management System`. It is intentionally narrower than a formal certification pack: it captures what CT2 can actually prove today, the repeatable checks that produce that evidence, and the remaining gaps that are still technical debt rather than hidden assumptions.

## Evidence Sources
- `bash ct2_back_office/scripts/ct2_lint.sh`
- `php ct2_back_office/scripts/ct2_smoke_check.php`
- `php ct2_back_office/scripts/ct2_db_smoke_check.php`
- `bash ct2_back_office/scripts/ct2_api_post_regression_check.sh`
- `bash ct2_back_office/scripts/ct2_nfr_sanity_check.sh`
- `bash ct2_back_office/scripts/ct2_route_matrix_check.sh`
- `bash ct2_back_office/scripts/ct2_runtime_hardening_check.sh`
- `docs/ct2_performance_accessibility_evidence.md`
- `docs/ct2_windows_xampp_validation_pack.md`
- `docs/ct2_manual_qa_pack.md`
- `docs/ct2_api_validation.md`
- `docs/ct2_qa_execution_report.md`
- `docs/ct2_deployment_guide.md`
- `docs/ct2_operator_runbook.md`

## Directly Proven
### Security controls
- Session-backed authentication and role-based route/API restrictions are exercised through the DB smoke check, manual QA pack, runtime hardening script, and the API POST regression script.
- Invalid CSRF tokens are now directly proven to be rejected for representative protected browser writes in approvals, supplier onboarding, availability resource creation, marketing campaign save, visa checklist verification, and financial report generation.
- Stale-session write attempts are directly proven to be redirected to the login flow without persisting an update on both agent and financial write paths.
- Protected API failures are directly proven to stay JSON-shaped on representative `401`, `403`, `405`, and `422` paths.

### Audit logging
- Representative write paths now have direct repeatable audit evidence:
  `auth.api_login`,
  `agents.update`,
  `agents.api_create`,
  `suppliers.onboarding_update`,
  `suppliers.api_create`,
  `suppliers.api_onboarding_update`,
  `suppliers.api_contract_create`,
  `suppliers.api_kpi_create`,
  `suppliers.contract_create`,
  `suppliers.kpi_create`,
  `suppliers.note_create`,
  `approvals.decide`,
  `approvals.api_decide`,
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
  `staff.api_create`,
  `visa.document_checklist_update`,
  `visa.api_application_create`,
  `visa.api_checklist_update`,
  `visa.api_payment_create`,
  `visa.api_status_update`,
  `visa.payment_create`,
  `visa.notification_create`,
  `visa.note_create`,
  `financial.api_report_create`,
  `financial.api_flag_update`,
  `financial.filter_create`,
  `financial.run_generate`,
  `financial.flag_update`.
- Negative security paths are also checked to confirm invalid-CSRF and stale-session writes do not create audit entries for the attempted action.

### Runtime quality on exercised flows
- The current lint, structural smoke, DB smoke, API POST regression, route matrix, NFR sanity, and runtime hardening checks run without PHP warnings, notices, or fatal errors on the exercised CT2 paths.
- The API POST regression script now covers:
  auth login,
  anonymous denial,
  malformed-payload `422` handling,
  representative create/update flows across agents, staff, suppliers, approvals, availability, marketing, visa, and financial modules,
  and permission-sensitive `403` handling for financial write endpoints.
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
- The repo now includes a Windows XAMPP validation pack that mirrors the native Linux validation flow without introducing Windows-specific code behavior.

## Partially Proven
### Browser and module breadth
- All primary CT2 module routes, seeded filter variants, representative JSON GET endpoints, and major mutation flows are now script-covered.
- Remaining manual-heavy coverage is concentrated in role-specific UAT sequencing, browser focus behavior, and operator judgment around workflow usability rather than basic persistence or route health.

### Warning-free runtime scope
- Warning-free execution is directly proven for the route matrix, the expanded hardening flows, DB smoke, and the previously executed manual QA scenarios.
- It is still not claimed as exhaustive for every reachable route/action pair or arbitrary parameter combination in CT2.

### Accessibility and performance
- Local performance sanity is now directly measured through `ct2_nfr_sanity_check.sh` and recorded in `docs/ct2_performance_accessibility_evidence.md`.
- Accessibility evidence is stronger than before because structural heading and form-label checks are now automated, but keyboard-only navigation, focus visibility, screen-reader behavior, and load behavior still remain outside the current direct evidence.

## Remaining Gaps
- Accessibility keyboard/focus evidence and broader load behavior remain validation gaps.
- Cross-platform compatibility is documented and code-aligned, but runtime evidence in-repo is still strongest on the local Linux LAMP environment rather than an executed Windows XAMPP run.

## Current Recommendation
- Treat CT2 security, audit, API write coverage, route breadth, and runtime-hardening evidence as materially stronger than the earlier release-only state.
- Treat keyboard/accessibility follow-up, broader load evidence, and executed Windows XAMPP evidence as the main remaining repo-owned non-functional debt.
