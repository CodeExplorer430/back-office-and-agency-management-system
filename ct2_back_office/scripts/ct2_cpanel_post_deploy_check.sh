#!/usr/bin/env bash

set -euo pipefail

ct2_repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ct2_script_root="$ct2_repo_root/ct2_back_office/scripts"
ct2_warning_pattern='Warning:|Notice:|Deprecated:|PHP Warning|PHP Notice|PHP Deprecated|Fatal error|Parse error'
ct2_skip_live_http="${CT2_SKIP_LIVE_HTTP:-0}"

ct2_run_step() {
  local ct2_label="$1"
  shift

  local ct2_output_file
  ct2_output_file="$(mktemp)"
  trap 'rm -f "$ct2_output_file"' RETURN

  printf '[ct2-cpanel-check] Running %s.\n' "$ct2_label"
  if ! (
    cd "$ct2_repo_root"
    "$@"
  ) > >(tee "$ct2_output_file") 2> >(tee -a "$ct2_output_file" >&2); then
    printf '[ct2-cpanel-check] FAILED: %s\n' "$ct2_label" >&2
    exit 1
  fi

  if grep -E "$ct2_warning_pattern" "$ct2_output_file" >/dev/null 2>&1; then
    printf '[ct2-cpanel-check] FAILED: %s emitted warning/error output.\n' "$ct2_label" >&2
    exit 1
  fi

  rm -f "$ct2_output_file"
  trap - RETURN
  printf '[ct2-cpanel-check] Passed: %s\n' "$ct2_label"
}

ct2_run_step 'PHP lint' bash "$ct2_script_root/ct2_lint.sh"
ct2_run_step 'structural smoke' php "$ct2_script_root/ct2_smoke_check.php"
ct2_run_step 'DB smoke' php "$ct2_script_root/ct2_db_smoke_check.php"
ct2_run_step 'route matrix' bash "$ct2_script_root/ct2_route_matrix_check.sh"
ct2_run_step 'runtime hardening' bash "$ct2_script_root/ct2_runtime_hardening_check.sh"

if [[ "$ct2_skip_live_http" != '1' ]]; then
  ct2_run_step 'live HTTP health' bash "$ct2_script_root/ct2_live_http_health_check.sh"
fi

printf '[ct2-cpanel-check] CT2 cPanel post-deploy verification passed.\n'
