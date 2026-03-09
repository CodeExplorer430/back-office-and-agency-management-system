# CT2 API Validation

## Purpose
Use this checklist after importing the seeded CT2 database to verify representative JSON API behavior for each implemented module family.

## Preconditions
- Import `ct2_back_office/ct2_setup.sql`.
- Run `php ct2_back_office/scripts/ct2_db_smoke_check.php`.
- Run `bash ct2_back_office/scripts/ct2_api_post_regression_check.sh` for the scripted POST regression pass.
- Run `bash ct2_back_office/scripts/ct2_role_uat_check.sh` when permission-boundary behavior is part of the validation pass.
- Authenticate with a seeded account that has `api.access`, or use the browser session after logging in with `ct2admin`.

## Core API Expectations
- Every endpoint returns `Content-Type: application/json`.
- Every response follows the CT2 envelope shape: `success`, `data`, `error`, `meta`.
- Unauthorized access returns `403`.
- Unsupported methods return `405` where the endpoint enforces method restrictions.
- Error responses must not include HTML fragments or PHP warnings.

## Representative Endpoint Checklist
### Authentication and platform
- `POST /api/ct2_auth_login.php`
  Use `ct2admin` / `ChangeMe123!` and confirm success plus session creation.
- `GET /api/ct2_module_status.php`
  Confirm all six CT2 modules report as implemented.

### Agents and staff
- `GET /api/ct2_agents.php`
  Confirm seeded agents such as `AGT-CT2-001` are returned.
- `GET /api/ct2_staff.php`
  Confirm seeded staff codes `STF-CT2-001` to `STF-CT2-004` are visible.
- `POST /api/ct2_approvals.php`
  Approve one pending seeded workflow and confirm the response is JSON-only.

### Supplier and availability
- `GET /api/ct2_suppliers.php`
  Confirm `SUP-CT2-001` and `SUP-CT2-002` are returned.
- `GET /api/ct2_supplier_onboarding.php`
  Confirm the blocked onboarding state for `SUP-CT2-002`.
- `GET /api/ct2_supplier_contracts.php`
  Confirm seeded contracts `CTR-CT2-001` and `CTR-CT2-002`.
- `GET /api/ct2_tour_availability.php`
  Confirm seeded resources and allocations include booking `CT1-BKG-1001`.
- `GET /api/ct2_dispatch_orders.php`
  Confirm the seeded dispatch order tied to vehicle `NAA-4581`.

### Marketing
- `GET /api/ct2_marketing_campaigns.php`
  Confirm one active and one pending-approval campaign.
- `GET /api/ct2_promotions.php`
  Confirm seeded promotions `PROMO-CT2-001` and `PROMO-CT2-002`.
- `GET /api/ct2_vouchers.php`
  Confirm seeded vouchers `VOUCH-CT2-001` and `VOUCH-CT2-002`.
- `GET /api/ct2_affiliates.php`
  Confirm seeded affiliate codes `AFF-CT2-001` and `AFF-CT2-002`.
- `GET /api/ct2_marketing_reports.php`
  Confirm campaign metrics and revenue totals are returned.

### Visa
- `GET /api/ct2_visa_applications.php`
  Confirm `VISA-APP-001` and `VISA-APP-002` are returned with different statuses.
- `GET /api/ct2_visa_checklists.php`
  Confirm the seeded application checklist rows exist.
- `GET /api/ct2_visa_payments.php`
  Confirm `PAY-VISA-001` and `PAY-VISA-002` are visible.
- `POST /api/ct2_visa_status.php`
  Update a seeded application status and confirm the approval-rule behavior stays consistent.

### Financial
- `GET /api/ct2_financial_reports.php`
  Confirm the report catalog includes `CT2-OPS-001`.
- `GET /api/ct2_financial_snapshots.php`
  Confirm the seeded run `QA Baseline Cross-Module Run` returns snapshots.
- `GET /api/ct2_financial_exports.php`
  Confirm export metadata or output is available for a seeded run.

## Negative Checks
- Call one protected endpoint without an authenticated session and confirm `403`.
- Send an unsupported method to one POST-oriented endpoint and confirm `405`.
- Send malformed payload data to one write endpoint and confirm JSON error handling, not HTML failure output.

## Scripted Baseline
- `ct2_api_post_regression_check.sh` now covers representative success, malformed-payload, and permission-boundary checks for the stable CT2 POST endpoints.
- `ct2_role_uat_check.sh` complements the API pass by proving the seeded browser-side permission boundaries for manager, front desk, and accounting roles.
- Manual API validation should focus on endpoint families not yet included in the scripted regression pass or on business-intent interpretation beyond raw contract safety.
