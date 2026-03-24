#!/usr/bin/env bash

set -euo pipefail

ct2_repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ct2_output_dir="$ct2_repo_root/dist"
ct2_version=''

while [[ $# -gt 0 ]]; do
  case "$1" in
    --output-dir)
      ct2_output_dir="$2"
      shift 2
      ;;
    --version)
      ct2_version="$2"
      shift 2
      ;;
    *)
      printf 'Unknown option: %s\n' "$1" >&2
      exit 1
      ;;
  esac
done

if [[ -z "$ct2_version" ]]; then
  ct2_short_sha="$(git -C "$ct2_repo_root" rev-parse --short HEAD 2>/dev/null || printf 'unknown')"
  ct2_version="release-$(date -u +%Y%m%d%H%M%S)-$ct2_short_sha"
fi

ct2_commit_sha="$(git -C "$ct2_repo_root" rev-parse HEAD 2>/dev/null || printf 'unknown')"
ct2_built_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
ct2_release_name="ct2-${ct2_version}"
ct2_stage_dir="$(mktemp -d)"
ct2_package_root="$ct2_stage_dir/$ct2_release_name"
ct2_artifact_path="$ct2_output_dir/${ct2_release_name}.tar.gz"

cleanup() {
  rm -rf "$ct2_stage_dir"
}
trap cleanup EXIT

mkdir -p "$ct2_output_dir" "$ct2_package_root/docs"

cp -R "$ct2_repo_root/ct2_back_office" "$ct2_package_root/"
cp \
  "$ct2_repo_root/docs/ct2_deployment_guide.md" \
  "$ct2_repo_root/docs/ct2_operator_runbook.md" \
  "$ct2_repo_root/docs/ct2_quality_gate.md" \
  "$ct2_repo_root/docs/ct2_cpanel_release_flow.md" \
  "$ct2_package_root/docs/"

rm -f "$ct2_package_root/ct2_back_office/config/ct2_local.php"
mkdir -p \
  "$ct2_package_root/ct2_back_office/storage/uploads" \
  "$ct2_package_root/ct2_back_office/storage/sessions"
find "$ct2_package_root/ct2_back_office/storage/uploads" -mindepth 1 -delete
find "$ct2_package_root/ct2_back_office/storage/sessions" -mindepth 1 -delete

cat > "$ct2_package_root/ct2_release_manifest.json" <<EOF
{
  "application": "CORE TRANSACTION 2",
  "release_name": "$ct2_release_name",
  "version": "$ct2_version",
  "git_sha": "$ct2_commit_sha",
  "built_at_utc": "$ct2_built_at",
  "quality_gate": "ct2_validation_suite.sh",
  "deployment_target": "cpanel-artifact"
}
EOF

tar -czf "$ct2_artifact_path" -C "$ct2_stage_dir" "$ct2_release_name"

(cd "$ct2_output_dir" && \
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$(basename "$ct2_artifact_path")" > "$(basename "$ct2_artifact_path").sha256"
  else
    shasum -a 256 "$(basename "$ct2_artifact_path")" > "$(basename "$ct2_artifact_path").sha256"
  fi)

printf '[ct2-release] Created %s\n' "$ct2_artifact_path"
printf '[ct2-release] SHA256 file %s.sha256\n' "$ct2_artifact_path"
