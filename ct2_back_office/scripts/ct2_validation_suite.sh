#!/usr/bin/env bash
set -euo pipefail

ct2_repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ct2_script_root="$ct2_repo_root/ct2_back_office/scripts"

ct2_warning_pattern='Warning:|Notice:|Deprecated:|PHP Warning|PHP Notice|PHP Deprecated|Fatal error|Parse error'

ct2_run_step() {
  local ct2_label="$1"
  shift

  local ct2_output_file
  ct2_output_file="$(mktemp)"
  trap 'rm -f "$ct2_output_file"' RETURN

  printf '[ct2-suite] Running %s.\n' "$ct2_label"
  if ! (
    cd "$ct2_repo_root"
    "$@"
  ) > >(tee "$ct2_output_file") 2> >(tee -a "$ct2_output_file" >&2); then
    printf '[ct2-suite] FAILED: %s\n' "$ct2_label" >&2
    exit 1
  fi

  if grep -E "$ct2_warning_pattern" "$ct2_output_file" >/dev/null 2>&1; then
    printf '[ct2-suite] FAILED: %s emitted warning/error output that is not allowed under the strict gate.\n' "$ct2_label" >&2
    exit 1
  fi

  printf '[ct2-suite] Passed: %s\n' "$ct2_label"
  rm -f "$ct2_output_file"
  trap - RETURN
}

ct2_run_step 'format check' bash "$ct2_script_root/ct2_format_check.sh"
ct2_run_step 'PHP lint' bash "$ct2_script_root/ct2_lint.sh"
ct2_run_step 'structural smoke' php "$ct2_script_root/ct2_smoke_check.php"
ct2_run_step 'DB smoke' php "$ct2_script_root/ct2_db_smoke_check.php"
ct2_run_step 'browser accessibility' bash "$ct2_script_root/ct2_browser_accessibility_check.sh"
ct2_run_step 'UI regression' bash "$ct2_script_root/ct2_ui_regression_check.sh"
ct2_run_step 'load profile' bash "$ct2_script_root/ct2_load_profile_check.sh"
ct2_run_step 'route matrix' bash "$ct2_script_root/ct2_route_matrix_check.sh"
ct2_run_step 'runtime hardening' bash "$ct2_script_root/ct2_runtime_hardening_check.sh"
ct2_run_step 'API POST regression' bash "$ct2_script_root/ct2_api_post_regression_check.sh"
ct2_run_step 'NFR sanity' bash "$ct2_script_root/ct2_nfr_sanity_check.sh"
ct2_run_step 'role UAT' bash "$ct2_script_root/ct2_role_uat_check.sh"

printf '[ct2-suite] CT2 strict validation suite passed with zero tolerated warnings.\n'
