# CT2 Operator Runbook

## Purpose
This runbook is for the technical deployment or support team responsible for operating CT2 after handoff. It assumes the application stays on a Vanilla PHP + MySQL stack and that `develop` is the integration baseline.

## Current Quality Gate
- The active release/process policy is the strict full-suite gate in `docs/ct2_quality_gate.md`.
- No deployment, rollback completion, or support signoff is clean while any warning, notice, deprecation, or failed validator remains open.
- The strict suite now includes a dedicated UI regression runner in addition to browser accessibility, route, runtime, and API checks.

## Related Guides
- Setup and environment preparation: `docs/ct2_setup_guide.md`
- Integration and system-boundary guidance: `docs/ct2_integration_guide.md`
- Expanded troubleshooting steps: `docs/ct2_troubleshooting_guide.md`
- cPanel release mechanics: `docs/ct2_cpanel_release_flow.md`

## Branch And Release Policy
- `develop` is the current integrated CT2 branch.
- `main` is the release branch.
- The current approved CT2 release is tracked in `docs/ct2_release_summary_2026-03-25-r2.md`.
- GitHub Actions is the blocking release gate and cPanel is the primary deployment target.
- Windows XAMPP evidence remains a deferred cross-platform validation task. It should be completed and copied back into the repo, but it no longer blocks promotion of the current approved release state into `main`.
- Do not deploy directly from unreviewed feature branches.
- Promotion flow:
  validate on `develop`,
  confirm the strict full suite and QA acceptance,
  merge or fast-forward the approved release state into `main`,
  build the validated artifact,
  then deploy that artifact to cPanel.

## Deployment Procedure
### Fresh deployment
1. Build or download the validated release artifact.
2. Create the cPanel shared paths for config and writable storage.
3. Import `ct2_back_office/ct2_setup.sql` into a clean `ct2_back_office` database.
4. Put `ct2_local.php` in the shared config path, not inside the release tree.
5. If CT2 must share the main domain under a path such as `/ct2`, set `CT2_CPANEL_PUBLIC_PATH` to the matching public path, for example `/home/<user>/public_html/ct2`.
6. Deploy with `bash ct2_back_office/scripts/ct2_cpanel_deploy.sh`.
7. Execute the acceptance flow from `docs/ct2_manual_qa_pack.md`.

### Update deployment
1. Back up the existing MySQL database.
2. Back up `ct2_back_office/storage/uploads/` if end users have uploaded files.
3. Build or download the validated artifact for the approved release state.
4. Review `ct2_back_office/ct2_setup.sql` for schema or seed changes.
5. Re-import or apply the updated SQL only after confirming backup completion.
6. Run `bash ct2_back_office/scripts/ct2_cpanel_deploy.sh`.
7. Treat any warning, notice, deprecation, failed pre-live check, or failed live HTTP health check as a failed update deployment.
8. If the target is Windows XAMPP, update the evidence table in `docs/ct2_windows_xampp_validation_pack.md`.
9. If the target is Windows XAMPP, return the completed `docs/ct2_windows_xampp_result_template.md` so the repo evidence can be updated without interpretation drift.
10. If the target is Windows XAMPP, prefer the `.ps1` validation entrypoints and `ct2_validation_suite.ps1` from PowerShell, then return the completed evidence template for repo ingestion.

## Configuration Rules
- Prefer explicit TCP MySQL values in `ct2_local.php`.
- Environment variables override `ct2_local.php` when both are present.
- Never commit local credentials.
- Keep the storage path local to the target machine; do not redirect uploads or sessions into tracked repo paths.
- On shared cPanel hosting, keep CT2 releases under a dedicated deploy root and expose the active release through `CT2_CPANEL_PUBLIC_PATH` rather than mixing CT2 files into another system's web directory.

## Backups And Rollback
### Minimum backup scope
- MySQL database `ct2_back_office`
- `ct2_back_office/storage/uploads/`
- local deployment config outside version control if required by the hosting team

### Rollback approach
1. Restore the previous `current` symlink or code revision.
2. Restore the database backup taken before the failed deployment.
3. Restore uploaded files if the deployment affected `storage/uploads/`.
4. Re-run the cPanel post-deploy verification and live HTTP health check before reopening access.

## Operational Checks
### Daily or release-day checks
- Confirm login works for an authorized user.
- Confirm dashboard loads without PHP warnings.
- Confirm approval queue renders.
- Confirm a representative JSON endpoint returns valid JSON.
- Confirm the strict validation suite passed in GitHub Actions for the deployed artifact.
- Confirm the cPanel post-deploy verification and live health check passed on the active target.
- Confirm financial CSV export still downloads.
- Confirm visa upload still writes to `storage/uploads/`.

### When demo/UAT data is needed
- Use the seeded accounts documented in `docs/ct2_manual_qa_pack.md`.
- Treat seeded credentials as temporary validation credentials only.

## Troubleshooting
Use `docs/ct2_troubleshooting_guide.md` for the detailed recovery playbook. The checks below remain the operator quick-reference.

### Database connection failure
- Check `ct2_back_office/config/ct2_local.php`.
- Confirm the MySQL host, port, database, username, and password.
- Confirm `pdo_mysql` is enabled.
- Run `php ct2_back_office/scripts/ct2_db_smoke_check.php`.

### Session or login problems
- Check that `ct2_back_office/storage/sessions/` exists and is writable.
- Clear stale session files only if required by the support process.
- Confirm the seeded or assigned user is active and has a role.

### Upload failure
- Check that `ct2_back_office/storage/uploads/` exists and is writable.
- Check PHP upload limits and file size settings.
- Confirm the uploaded document matches the allowed file types in the visa workflow.

### API problems
- Confirm the user has `api.access` when required, plus any module-specific permission expected for the same action in the browser.
- Verify the endpoint still returns `application/json`.
- Check for PHP notices or HTML leakage in the response body.

### Export problems
- Confirm the user has `financial.export`.
- Confirm the selected report run exists.
- Check that the browser or server is not stripping the CSV response headers.

## Support Boundaries
- CT2 references external customer, booking, payment, and supplier ownership where the broader ERP owns the source data.
- Do not treat CT2 as the system of record for Financials, CT1 booking, or external identity systems.
- When production incidents cross those boundaries, collect the CT2 reference IDs and escalate to the owning system team.
