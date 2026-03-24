# CT2 Non-Functional Evidence

## Purpose
This document records the direct non-functional evidence that currently exists in the repository for the integrated `CORE TRANSACTION 2: Back-Office and Agency Management System`. It is intentionally narrower than a formal certification pack: it captures what CT2 can actually prove today, the repeatable checks that produce that evidence, and the remaining gaps that are still technical debt rather than hidden assumptions.

## Current Quality Gate
- The mandatory enforcement point for this evidence is `docs/ct2_quality_gate.md`.
- NFR evidence is only current when the strict full suite passes with zero tolerated warnings, notices, deprecations, or validator failures.

## Evidence Sources
- `bash ct2_back_office/scripts/ct2_validation_suite.sh`
- `bash ct2_back_office/scripts/ct2_format_check.sh`
- `bash ct2_back_office/scripts/ct2_lint.sh`
- `php ct2_back_office/scripts/ct2_smoke_check.php`
- `php ct2_back_office/scripts/ct2_db_smoke_check.php`
- `bash ct2_back_office/scripts/ct2_api_post_regression_check.sh`
- `bash ct2_back_office/scripts/ct2_browser_accessibility_check.sh`
- `bash ct2_back_office/scripts/ct2_ui_regression_check.sh`
- `bash ct2_back_office/scripts/ct2_load_profile_check.sh`
- `bash ct2_back_office/scripts/ct2_nfr_sanity_check.sh`
- `bash ct2_back_office/scripts/ct2_role_uat_check.sh`
- `bash ct2_back_office/scripts/ct2_route_matrix_check.sh`
- `bash ct2_back_office/scripts/ct2_runtime_hardening_check.sh`
- `docs/ct2_performance_accessibility_evidence.md`
- `docs/ct2_windows_xampp_validation_pack.md`
- `docs/ct2_manual_qa_pack.md`
- `docs/ct2_api_validation.md`
- `docs/ct2_qa_execution_report.md`
- `docs/ct2_deployment_guide.md`
- `docs/ct2_operator_runbook.md`
- `docs/ct2_quality_gate.md`

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
- The UI regression script now directly proves the shared UI contract for:
  expanded and collapsed sidebar geometry,
  collapsed logo and active-state alignment,
  modal top-level mounting,
  centered and clickable modal dialogs,
  long-form modal footer safety,
  tab and pagination query-state preservation,
  toast-versus-modal stack order,
  and split date/time modal controls on visa and availability flows.
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
- The role-specific UAT script now directly proves:
  `ct2manager` access to approvals and marketing plus a live approval decision submission,
  `ct2desk` access to visa with financial denial,
  and `ct2finance` access to financial reporting plus CSV export.

### Cross-platform runtime contract
- The application still uses the same TCP-first MySQL configuration contract for LAMP and Windows XAMPP:
  `host`, `port`, `name`, `username`, `password`, `charset`.
- Session and upload storage remain relative to `ct2_back_office/storage/`, which keeps the runtime contract portable between Linux and Windows file layouts.
- The repo now includes a Windows XAMPP validation pack that mirrors the native Linux validation flow without introducing Windows-specific code behavior.
- The repo now includes PowerShell entrypoints for the major validation surface so Windows operators can launch CT2 validation from native PowerShell instead of starting from Bash commands.
- Route matrix, UI regression, NFR sanity, load profile, role UAT, and browser accessibility now run through shared PHP or JS validators that are invoked from both Bash and PowerShell.

## Partially Proven
### Warning-free runtime scope
- Warning-free execution is directly proven for the route matrix, the expanded hardening flows, DB smoke, and the previously executed manual QA scenarios.
- It is still not claimed as exhaustive for every reachable route/action pair or arbitrary parameter combination in CT2.

### Cross-platform execution breadth
- The Linux-side runtime, keyboard/focus, repeated load, and role/UAT evidence is now directly captured in-repo.
- The remaining cross-platform gaps are the executed Windows XAMPP run and the removal of Bash delegation from `ct2_runtime_hardening_check.ps1` and `ct2_api_post_regression_check.ps1`.

## Remaining Gaps
- Cross-platform compatibility is documented and code-aligned, but runtime evidence in-repo is still strongest on the local Linux LAMP environment rather than an executed Windows XAMPP run.
- Formatting checks now exist repo-wide, but they must still be kept in the active validation workflow whenever docs, scripts, SQL, or CSS are touched.

## Current Recommendation
- Treat CT2 security, audit, API write coverage, route breadth, keyboard/focus reachability, repeated load sanity, and role/UAT evidence as materially stronger than the earlier release-only state.
- Treat the executed Windows XAMPP run as the main remaining repo-owned non-functional validation debt.
- Treat that Windows evidence gap as deferred cross-platform follow-up rather than a blocker for the current approved `main` release baseline.
