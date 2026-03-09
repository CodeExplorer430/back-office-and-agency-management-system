# CT2 Operator Runbook

## Purpose
This runbook is for the technical deployment or support team responsible for operating CT2 after handoff. It assumes the application stays on a Vanilla PHP + MySQL stack and that `develop` is the integration baseline.

## Branch And Release Policy
- `develop` is the current integrated CT2 branch.
- `main` is the release branch.
- The current approved CT2 release is tracked in `docs/ct2_release_summary_2026-03-10.md`.
- Do not deploy directly from unreviewed feature branches.
- Promotion flow:
  validate on `develop`,
  confirm lint, smoke, DB smoke, and QA acceptance,
  then merge or fast-forward the approved release state into `main`.

## Deployment Procedure
### Fresh deployment
1. Pull the approved CT2 branch onto the target host.
2. Create `ct2_back_office/config/ct2_local.php` from the example file.
3. Import `ct2_back_office/ct2_setup.sql` into a clean `ct2_back_office` database.
4. Confirm writable `storage/sessions/` and `storage/uploads/`.
5. Run:
   `bash ct2_back_office/scripts/ct2_lint.sh`
   `php ct2_back_office/scripts/ct2_smoke_check.php`
   `php ct2_back_office/scripts/ct2_db_smoke_check.php`
6. Execute the acceptance flow from `docs/ct2_manual_qa_pack.md`.

### Update deployment
1. Back up the existing MySQL database.
2. Back up `ct2_back_office/storage/uploads/` if end users have uploaded files.
3. Pull the approved branch or release tag.
4. Review `ct2_back_office/ct2_setup.sql` for schema or seed changes.
5. Re-import or apply the updated SQL only after confirming backup completion.
6. Re-run lint, smoke, and DB smoke checks.
7. Re-run the route/API validation steps most affected by the change.

## Configuration Rules
- Prefer explicit TCP MySQL values in `ct2_local.php`.
- Environment variables override `ct2_local.php` when both are present.
- Never commit local credentials.
- Keep the storage path local to the target machine; do not redirect uploads or sessions into tracked repo paths.

## Backups And Rollback
### Minimum backup scope
- MySQL database `ct2_back_office`
- `ct2_back_office/storage/uploads/`
- local deployment config outside version control if required by the hosting team

### Rollback approach
1. Restore the previous code revision.
2. Restore the database backup taken before the failed deployment.
3. Restore uploaded files if the deployment affected `storage/uploads/`.
4. Run the smoke and DB smoke scripts before reopening access.

## Operational Checks
### Daily or release-day checks
- Confirm login works for an authorized user.
- Confirm dashboard loads without PHP warnings.
- Confirm approval queue renders.
- Confirm a representative JSON endpoint returns valid JSON.
- Confirm financial CSV export still downloads.
- Confirm visa upload still writes to `storage/uploads/`.

### When demo/UAT data is needed
- Use the seeded accounts documented in `docs/ct2_manual_qa_pack.md`.
- Treat seeded credentials as temporary validation credentials only.

## Troubleshooting
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
- Confirm the user has `api.access` when required.
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
