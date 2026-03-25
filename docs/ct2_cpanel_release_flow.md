# CT2 cPanel Release Flow

## Purpose
This document defines the release path for the primary CT2 hosting target: cPanel with SSH access. The workflow is intentionally split into an off-host blocking gate and an on-host post-deploy verification pass.

## Related Guides
- Setup and environment preparation: `docs/ct2_setup_guide.md`
- Integration model and external boundaries: `docs/ct2_integration_guide.md`
- Troubleshooting and rollback detail: `docs/ct2_troubleshooting_guide.md`
- Deployment overview: `docs/ct2_deployment_guide.md`

## Release Model
1. Validate the candidate revision in GitHub Actions with the full strict suite.
2. Build a release artifact with `bash ct2_back_office/scripts/ct2_release_artifact.sh`.
3. Upload the validated artifact to cPanel over SSH.
4. Extract the artifact into a new release directory.
5. Re-link shared config and writable storage.
6. Run `bash ct2_back_office/scripts/ct2_cpanel_post_deploy_check.sh` on the new release before it goes live.
7. Switch the `current` symlink to the new release.
8. Run `bash ct2_back_office/scripts/ct2_live_http_health_check.sh` against the live cPanel URL.
9. Roll back immediately if any post-switch health check fails.

## Required GitHub Secrets
- `CT2_CPANEL_HOST`
- `CT2_CPANEL_PORT`
- `CT2_CPANEL_USER`
- `CT2_CPANEL_SSH_KEY`
- `CT2_CPANEL_DEPLOY_PATH`
- `CT2_CPANEL_BASE_URL`
- `CT2_CPANEL_HEALTHCHECK_USERNAME`
- `CT2_CPANEL_HEALTHCHECK_PASSWORD`

## Optional GitHub Secrets
- `CT2_CPANEL_PUBLIC_PATH`
  Use this when CT2 is exposed under a shared-domain path such as `/ct2` instead of a dedicated cPanel document root. The workflow passes it through to the deploy script, which maintains a symlink like `public_html/ct2 -> <deploy-path>/current/ct2_back_office`.

## Required cPanel Directory Contract
- Release root: `<deploy-path>/releases/<release-name>`
- Active symlink: `<deploy-path>/current`
- Shared config: `<deploy-path>/shared/config/ct2_local.php`
- Shared uploads: `<deploy-path>/shared/storage/uploads/`
- Shared sessions: `<deploy-path>/shared/storage/sessions/`
- cPanel document root should resolve to `<deploy-path>/current/ct2_back_office`

## Shared-Domain Path Contract
- For shared cPanel accounts hosting multiple systems on one domain, keep CT2 isolated outside `public_html` at a dedicated deploy root such as `/home/<user>/apps/ct2`.
- Set `CT2_CPANEL_PUBLIC_PATH` to a public path such as `/home/<user>/public_html/ct2`.
- The deploy workflow will refresh that path to point at `<deploy-path>/current/ct2_back_office` on each successful release.
- Set `CT2_CPANEL_BASE_URL` to the matching public URL, for example `https://example.com/ct2`.
- Do not place CT2 releases, shared config, or writable storage directly inside another system's public tree.

## Validation Split
### In GitHub Actions
- Run `.github/workflows/ct2_quality_gate.yml`
- Require both the Bash and PowerShell strict suites to pass
- Publish the artifact only after the strict gate is fully green

### On the cPanel host
- Run:
  - `bash ct2_back_office/scripts/ct2_cpanel_post_deploy_check.sh`
  - `bash ct2_back_office/scripts/ct2_live_http_health_check.sh`
- Keep browser-heavy checks in GitHub Actions, not on cPanel

## Rollback Rule
- A failed pre-live verification blocks the symlink switch.
- A failed live HTTP health check after the switch must restore the previous `current` symlink before the release is considered complete.
- Database and uploads remain part of the operator backup scope from `docs/ct2_operator_runbook.md`.
