#!/usr/bin/env bash

set -euo pipefail

ct2_repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

ct2_require_env() {
  local ct2_var_name="$1"
  if [[ -z "${!ct2_var_name:-}" ]]; then
    printf 'Missing required environment variable: %s\n' "$ct2_var_name" >&2
    exit 1
  fi
}

ct2_require_env CT2_ARTIFACT_PATH
ct2_require_env CT2_DEPLOY_HOST
ct2_require_env CT2_DEPLOY_USER
ct2_require_env CT2_DEPLOY_PATH
ct2_require_env CT2_SSH_KEY_PATH
ct2_require_env CT2_BASE_URL

CT2_DEPLOY_PORT="${CT2_DEPLOY_PORT:-22}"
CT2_RELEASE_NAME="${CT2_RELEASE_NAME:-$(basename "$CT2_ARTIFACT_PATH" .tar.gz)}"
CT2_KEEP_RELEASES="${CT2_KEEP_RELEASES:-5}"
CT2_REMOTE_ARTIFACT="/tmp/${CT2_RELEASE_NAME}.tar.gz"
ct2_base_url_q="$(printf '%q' "$CT2_BASE_URL")"
ct2_health_user_q="$(printf '%q' "${CT2_HEALTHCHECK_USERNAME:-}")"
ct2_health_pass_q="$(printf '%q' "${CT2_HEALTHCHECK_PASSWORD:-}")"

if [[ ! -f "$CT2_ARTIFACT_PATH" ]]; then
  printf 'Artifact not found: %s\n' "$CT2_ARTIFACT_PATH" >&2
  exit 1
fi

ct2_ssh() {
  ssh -i "$CT2_SSH_KEY_PATH" -p "$CT2_DEPLOY_PORT" -o StrictHostKeyChecking=no \
    "$CT2_DEPLOY_USER@$CT2_DEPLOY_HOST" "$@"
}

ct2_scp() {
  scp -i "$CT2_SSH_KEY_PATH" -P "$CT2_DEPLOY_PORT" -o StrictHostKeyChecking=no \
    "$CT2_ARTIFACT_PATH" "$CT2_DEPLOY_USER@$CT2_DEPLOY_HOST:$CT2_REMOTE_ARTIFACT"
}

printf '[ct2-cpanel-deploy] Uploading validated artifact.\n'
ct2_scp

printf '[ct2-cpanel-deploy] Preparing remote release directories.\n'
ct2_ssh \
  "mkdir -p '$CT2_DEPLOY_PATH/releases' '$CT2_DEPLOY_PATH/shared/config' '$CT2_DEPLOY_PATH/shared/storage/uploads' '$CT2_DEPLOY_PATH/shared/storage/sessions'"

printf '[ct2-cpanel-deploy] Extracting release and linking shared state.\n'
ct2_ssh "
  set -euo pipefail
  ct2_release_dir='$CT2_DEPLOY_PATH/releases/$CT2_RELEASE_NAME'
  rm -rf \"\$ct2_release_dir\"
  mkdir -p \"\$ct2_release_dir\"
  tar -xzf '$CT2_REMOTE_ARTIFACT' -C \"\$ct2_release_dir\" --strip-components=1
  if [ ! -f '$CT2_DEPLOY_PATH/shared/config/ct2_local.php' ]; then
    echo 'Missing shared config: $CT2_DEPLOY_PATH/shared/config/ct2_local.php' >&2
    exit 1
  fi
  rm -f \"\$ct2_release_dir/ct2_back_office/config/ct2_local.php\"
  ln -sfn '$CT2_DEPLOY_PATH/shared/config/ct2_local.php' \"\$ct2_release_dir/ct2_back_office/config/ct2_local.php\"
  rm -rf \"\$ct2_release_dir/ct2_back_office/storage/uploads\" \"\$ct2_release_dir/ct2_back_office/storage/sessions\"
  ln -sfn '$CT2_DEPLOY_PATH/shared/storage/uploads' \"\$ct2_release_dir/ct2_back_office/storage/uploads\"
  ln -sfn '$CT2_DEPLOY_PATH/shared/storage/sessions' \"\$ct2_release_dir/ct2_back_office/storage/sessions\"
"

printf '[ct2-cpanel-deploy] Running pre-live verification inside the new release.\n'
ct2_ssh "
  set -euo pipefail
  cd '$CT2_DEPLOY_PATH/releases/$CT2_RELEASE_NAME'
  export CT2_SKIP_LIVE_HTTP=1
  bash ct2_back_office/scripts/ct2_cpanel_post_deploy_check.sh
"

printf '[ct2-cpanel-deploy] Switching current release.\n'
ct2_previous_release="$(ct2_ssh "if [ -L '$CT2_DEPLOY_PATH/current' ]; then readlink '$CT2_DEPLOY_PATH/current'; fi")"
ct2_ssh "ln -sfn '$CT2_DEPLOY_PATH/releases/$CT2_RELEASE_NAME' '$CT2_DEPLOY_PATH/current'"

printf '[ct2-cpanel-deploy] Running live HTTP health check against the cPanel URL.\n'
if ! ct2_ssh "
  set -euo pipefail
  cd '$CT2_DEPLOY_PATH/current'
  export CT2_BASE_URL=$ct2_base_url_q
  export CT2_HEALTHCHECK_USERNAME=$ct2_health_user_q
  export CT2_HEALTHCHECK_PASSWORD=$ct2_health_pass_q
  bash ct2_back_office/scripts/ct2_live_http_health_check.sh
"; then
  if [[ -n "$ct2_previous_release" ]]; then
    printf '[ct2-cpanel-deploy] Live check failed. Rolling back to %s.\n' "$ct2_previous_release" >&2
    ct2_ssh "ln -sfn '$ct2_previous_release' '$CT2_DEPLOY_PATH/current'"
  fi
  exit 1
fi

printf '[ct2-cpanel-deploy] Trimming old releases.\n'
ct2_ssh "
  set -euo pipefail
  cd '$CT2_DEPLOY_PATH/releases'
  ls -1dt */ 2>/dev/null | tail -n +$((CT2_KEEP_RELEASES + 1)) | xargs -r rm -rf --
  rm -f '$CT2_REMOTE_ARTIFACT'
"

printf '[ct2-cpanel-deploy] cPanel deployment completed successfully.\n'
