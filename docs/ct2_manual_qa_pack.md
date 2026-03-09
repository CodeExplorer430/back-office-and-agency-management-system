# CT2 Manual QA Pack

## Purpose
Use this guide to validate the integrated `CORE TRANSACTION 2: Back-Office and Agency Management System` on a clean LAMP or Windows XAMPP environment. The same seeded database supports both client-facing UAT and internal technical smoke coverage.

Read this after completing the install flow in `docs/ct2_deployment_guide.md`.

## Environment Setup
1. Copy `ct2_back_office/config/ct2_local.php.example` to `ct2_back_office/config/ct2_local.php` and set TCP MySQL credentials.
2. Import `ct2_back_office/ct2_setup.sql` into a clean `ct2_back_office` database.
3. Run:
   `bash ct2_back_office/scripts/ct2_lint.sh`
   `php ct2_back_office/scripts/ct2_smoke_check.php`
   `php ct2_back_office/scripts/ct2_db_smoke_check.php`
   `bash ct2_back_office/scripts/ct2_api_post_regression_check.sh`
   `bash ct2_back_office/scripts/ct2_nfr_sanity_check.sh`
   `bash ct2_back_office/scripts/ct2_route_matrix_check.sh`
   `bash ct2_back_office/scripts/ct2_runtime_hardening_check.sh`
4. Start the app and sign in through `ct2_back_office/ct2_index.php`.

## Seeded Accounts
All seeded users use the same initial password: `ChangeMe123!`

| Username | Role | Primary Use |
| --- | --- | --- |
| `ct2admin` | `system_admin` | Full control and cross-module verification |
| `ct2manager` | `back_office_manager` | Approvals, dashboard, supplier and marketing QA |
| `ct2lead` | `team_lead` | Planning, onboarding, availability, and follow-up workflows |
| `ct2desk` | `front_desk_agent` | Visa intake and front-office operational checks |
| `ct2finance` | `accounting_staff` | Financial reporting, reconciliation, and export checks |

## Seeded Demo Records
- Agents: `AGT-CT2-001` approved, `AGT-CT2-002` pending.
- Staff: `STF-CT2-001` to `STF-CT2-004`, with one active agent assignment already linked.
- Suppliers: `SUP-CT2-001` approved/live and `SUP-CT2-002` pending/in-review with onboarding, contract, KPI, and notes.
- Availability: packages `Northern Luzon Discovery QA` and `Cebu Harbor Escape QA`, linked resources, allocations, vehicle, driver, dispatch order, and maintenance log.
- Marketing: campaigns `CT2-MKT-001` active and `CT2-MKT-002` pending approval, with related promotions, vouchers, affiliates, clicks, redemptions, metrics, and notes.
- Visa: types `VISA-SG-TOUR` and `VISA-JP-BIZ`, applications `VISA-APP-001` and `VISA-APP-002`, seeded checklist rows, payment records, notifications, and case notes.
- Financial: seeded run `QA Baseline Cross-Module Run` with snapshots and open reconciliation flags.
- Approvals: pending workflow entries for supplier, campaign, promotion, and visa exception review.

## Client UAT Walkthrough
### 1. Login and dashboard review
- User: `ct2manager`
- Path: `Dashboard`
- Verify summary cards show seeded counts for agents, suppliers, resources, campaigns, visa applications, and financial snapshots.
- Verify the approval queue and dispatch table show seeded rows.

### 2. Agent and staff workflow
- User: `ct2manager`
- Path: `Agents`
- Verify `AGT-CT2-001` and `AGT-CT2-002` appear in the registry.
- Edit `AGT-CT2-002`, change approval to `approved`, save, and confirm the updated value persists.
- Verify the assignment table shows the seeded link between `AGT-CT2-001` and `STF-CT2-002`.

### 3. Supplier onboarding and contract review
- User: `ct2lead`
- Path: `Suppliers`
- Verify `SUP-CT2-002` shows pending approval and in-review onboarding.
- Open the onboarding tracker and confirm the blocked reason mentions the missing liability certificate.
- Confirm the seeded contract `CTR-CT2-002` and KPI watch flag are visible.
- Add a new relationship note and verify it appears in the supplier notes table.

### 4. Availability and dispatch workflow
- User: `ct2lead`
- Path: `Availability`
- Verify the seeded package, resources, and allocation for booking `CT1-BKG-1001`.
- Confirm the dispatch order uses vehicle `NAA-4581` and driver `Aris Navarro`.
- Add a new seasonal block or maintenance log and verify it is listed after save.

### 5. Marketing approvals and conversion tracking
- User: `ct2manager`
- Path: `Marketing`
- Verify `CT2-MKT-001` is active and `CT2-MKT-002` is pending approval.
- Confirm the seeded promotion, voucher, affiliate, referral click, redemption, and metrics data are visible.
- Update the pending campaign or promotion, save, and confirm the row remains tied to the approval queue.

### 6. Visa intake and checklist verification
- User: `ct2desk`
- Path: `Visa`
- Verify applications `VISA-APP-001` and `VISA-APP-002` appear with different statuses.
- Confirm the seeded payment and notification rows are visible.
- Use the checklist form to upload a local sample file such as a PDF or JPG for `VISA-APP-001`.
- Verify the checklist status updates and the uploaded document appears in the document list.

### 7. Approval queue decision
- User: `ct2manager`
- Path: `Approvals`
- Confirm there are pending approval rows for supplier, campaign, promotion, and visa exception review.
- Approve or reject one item, save decision notes, and verify the related module record reflects the new approval state.

### 8. Financial reporting and export
- User: `ct2finance`
- Path: `Financial`
- Filter to the seeded run `QA Baseline Cross-Module Run`.
- Verify snapshots and open flags are visible for suppliers and visa operations.
- Resolve one reconciliation flag and confirm the status changes.
- Export CSV for the selected run and verify the download contains seeded snapshot rows.

## Technical Validation Appendix
### Scripted baseline
- `ct2_api_post_regression_check.sh` now covers the stable JSON write endpoints for auth, agents, staff, suppliers, approvals, availability, marketing, visa, and financial flows.
- `ct2_nfr_sanity_check.sh` now covers structural heading/label checks plus seeded local timing samples for login, dashboard, filtered agent search, and the module-status API.
- `ct2_route_matrix_check.sh` now covers breadth for the main module routes, seeded filter variants, representative JSON GET endpoints, and financial CSV export.
- `ct2_runtime_hardening_check.sh` now covers representative positive and negative writes across agents, suppliers, approvals, availability, marketing, visa, and financial workflows with direct audit-log assertions.
- Manual QA should now focus on role-driven UAT behavior, operator judgment, and end-to-end sequencing rather than re-proving basic route health or representative persistence.

### Route coverage
- Validate navigation for: `dashboard`, `agents`, `suppliers`, `availability`, `marketing`, `financial`, `visa`, `staff`, `approvals`.
- Confirm unauthorized users cannot access routes outside their seeded role permissions.

### CSRF and session coverage
- Submit at least one state-changing form per module with a valid session.
- Confirm logout works and that a stale session cannot continue posting changes.
- Confirm invalid CSRF submissions are rejected for at least approvals, supplier onboarding, availability resource creation, marketing campaign save, visa checklist updates, and financial run/filter actions.

### Upload coverage
- Upload one local sample file through the visa checklist flow.
- Confirm the file lands under `ct2_back_office/storage/uploads/` and that a `ct2_documents` row is created.

### Database assertions
- `ct2_approval_workflows` should contain pending rows before approval QA begins.
- `ct2_report_runs` should include `QA Baseline Cross-Module Run`.
- `ct2_visa_applications` should include `VISA-APP-001` and `VISA-APP-002`.
- `ct2_campaigns` should include both an active and a pending-approval record.

### Audit-log assertions
- Agent save should create a new `ct2_audit_logs` row with `action_key = 'agents.update'`.
- Supplier onboarding save should create a new `ct2_audit_logs` row with `action_key = 'suppliers.onboarding_update'`.
- Approval decisions should create a new `ct2_audit_logs` row with `action_key = 'approvals.decide'`.
- Visa checklist verification should create a new `ct2_audit_logs` row with `action_key = 'visa.document_checklist_update'`.
- Financial reconciliation updates should create a new `ct2_audit_logs` row with `action_key = 'financial.flag_update'`.
- Invalid-CSRF and stale-session write attempts must not create new audit rows for the protected action being tested.
