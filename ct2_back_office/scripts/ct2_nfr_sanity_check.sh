#!/usr/bin/env bash

set -euo pipefail

CT2_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CT2_APP_DIR="$CT2_ROOT_DIR/ct2_back_office"
CT2_BASE_URL="http://127.0.0.1:8096/ct2_index.php"
CT2_API_BASE_URL="http://127.0.0.1:8096/api"
CT2_COOKIE_JAR="$(mktemp)"
CT2_TMP_DIR="$(mktemp -d)"
CT2_SERVER_LOG="$CT2_TMP_DIR/ct2_php_server.log"
CT2_SERVER_PID=""
CT2_MAX_SECONDS="5.00"

cleanup() {
    if [[ -n "$CT2_SERVER_PID" ]] && kill -0 "$CT2_SERVER_PID" >/dev/null 2>&1; then
        kill "$CT2_SERVER_PID" >/dev/null 2>&1 || true
        wait "$CT2_SERVER_PID" 2>/dev/null || true
    fi

    rm -f "$CT2_COOKIE_JAR"
    rm -rf "$CT2_TMP_DIR"
}

trap cleanup EXIT

log() {
    printf '[ct2-nfr] %s\n' "$1"
}

fail() {
    printf '[ct2-nfr] ERROR: %s\n' "$1" >&2
    exit 1
}

assert_file_contains() {
    local file_path="$1"
    local expected_text="$2"
    local message="$3"

    if ! grep -Fq "$expected_text" "$file_path"; then
        fail "$message"
    fi
}

assert_seconds_within_limit() {
    local measured="$1"
    local label="$2"

    php -r '
        $measured = (float) $argv[1];
        $limit = (float) $argv[2];
        exit($measured <= $limit ? 0 : 1);
    ' "$measured" "$CT2_MAX_SECONDS" || fail "$label exceeded ${CT2_MAX_SECONDS}s (measured ${measured}s)"
}

extract_csrf() {
    php -r '$html = file_get_contents($argv[1]); if ($html === false) { exit(1); } if (!preg_match("/name=\"ct2_csrf_token\" value=\"([^\"]+)\"/", $html, $m)) { exit(2); } echo $m[1];' "$1"
}

start_server() {
    php -S 127.0.0.1:8096 -t "$CT2_APP_DIR" >"$CT2_SERVER_LOG" 2>&1 &
    CT2_SERVER_PID="$!"

    for _ in $(seq 1 30); do
        if curl -sS -o /dev/null "$CT2_BASE_URL?module=auth&action=login"; then
            return 0
        fi
        sleep 1
    done

    fail "Unable to start the local CT2 PHP server. See $CT2_SERVER_LOG."
}

measure_get() {
    local url="$1"
    local body_file="$2"
    local header_file="$3"
    curl -sS -b "$CT2_COOKIE_JAR" -c "$CT2_COOKIE_JAR" -D "$header_file" -o "$body_file" -w '%{time_total}' "$url"
}

measure_post_form_follow() {
    local url="$1"
    local body_file="$2"
    local header_file="$3"
    shift 3
    curl -sS -L -b "$CT2_COOKIE_JAR" -c "$CT2_COOKIE_JAR" -D "$header_file" -o "$body_file" -w '%{time_total}' "$url" "$@"
}

check_heading_file() {
    local file_path="$1"
    assert_file_contains "$file_path" "<h2>" "Missing h2 heading in $file_path"
}

check_label_file() {
    local file_path="$1"
    assert_file_contains "$file_path" 'ct2-label' "Missing form label markup in $file_path"
}

log "Checking structural accessibility markers in CT2 views."
check_heading_file "$CT2_APP_DIR/views/auth/ct2_login.php"
check_heading_file "$CT2_APP_DIR/views/dashboard/ct2_home.php"
check_heading_file "$CT2_APP_DIR/views/agents/ct2_index.php"
check_heading_file "$CT2_APP_DIR/views/staff/ct2_index.php"
check_heading_file "$CT2_APP_DIR/views/suppliers/ct2_index.php"
check_heading_file "$CT2_APP_DIR/views/availability/ct2_index.php"
check_heading_file "$CT2_APP_DIR/views/marketing/ct2_index.php"
check_heading_file "$CT2_APP_DIR/views/financial/ct2_index.php"
check_heading_file "$CT2_APP_DIR/views/visa/ct2_index.php"
check_heading_file "$CT2_APP_DIR/views/approvals/ct2_index.php"

check_label_file "$CT2_APP_DIR/views/auth/ct2_login.php"
check_label_file "$CT2_APP_DIR/views/agents/ct2_index.php"
check_label_file "$CT2_APP_DIR/views/staff/ct2_index.php"
check_label_file "$CT2_APP_DIR/views/suppliers/ct2_index.php"
check_label_file "$CT2_APP_DIR/views/availability/ct2_index.php"
check_label_file "$CT2_APP_DIR/views/marketing/ct2_index.php"
check_label_file "$CT2_APP_DIR/views/financial/ct2_index.php"
check_label_file "$CT2_APP_DIR/views/visa/ct2_index.php"

log "Starting local CT2 PHP server."
start_server

log "Sampling seeded local response times."
CT2_HEADERS="$CT2_TMP_DIR/headers.txt"
CT2_BODY="$CT2_TMP_DIR/body.txt"

CT2_LOGIN_GET_TIME="$(measure_get "$CT2_BASE_URL?module=auth&action=login" "$CT2_BODY" "$CT2_HEADERS")"
assert_seconds_within_limit "$CT2_LOGIN_GET_TIME" "Login page"
assert_file_contains "$CT2_BODY" "CT2 Back-Office Login" "Login page did not render"
CT2_CSRF_TOKEN="$(extract_csrf "$CT2_BODY")"

CT2_LOGIN_POST_TIME="$(measure_post_form_follow "$CT2_BASE_URL?module=auth&action=login" "$CT2_BODY" "$CT2_HEADERS" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "username=ct2admin" \
    --data-urlencode "password=ChangeMe123!")"
assert_seconds_within_limit "$CT2_LOGIN_POST_TIME" "Login submit"
assert_file_contains "$CT2_BODY" "Back-Office Dashboard" "Login POST did not reach the dashboard"

CT2_DASHBOARD_TIME="$(measure_get "$CT2_BASE_URL?module=dashboard&action=index" "$CT2_BODY" "$CT2_HEADERS")"
assert_seconds_within_limit "$CT2_DASHBOARD_TIME" "Dashboard route"
assert_file_contains "$CT2_BODY" "Back-Office Dashboard" "Dashboard route did not render"

CT2_AGENTS_TIME="$(measure_get "$CT2_BASE_URL?module=agents&action=index&search=AGT-CT2-001" "$CT2_BODY" "$CT2_HEADERS")"
assert_seconds_within_limit "$CT2_AGENTS_TIME" "Agents filtered route"
assert_file_contains "$CT2_BODY" "AGT-CT2-001" "Agents filtered route did not render the seeded record"

CT2_MODULE_STATUS_TIME="$(measure_get "$CT2_API_BASE_URL/ct2_module_status.php" "$CT2_BODY" "$CT2_HEADERS")"
assert_seconds_within_limit "$CT2_MODULE_STATUS_TIME" "Module status API"
assert_file_contains "$CT2_BODY" '"success":true' "Module status API did not return a success payload"

log "Measured response times (seconds):"
printf 'login_get=%s\n' "$CT2_LOGIN_GET_TIME"
printf 'login_post=%s\n' "$CT2_LOGIN_POST_TIME"
printf 'dashboard=%s\n' "$CT2_DASHBOARD_TIME"
printf 'agents_filtered=%s\n' "$CT2_AGENTS_TIME"
printf 'module_status_api=%s\n' "$CT2_MODULE_STATUS_TIME"

log "CT2 NFR sanity checks passed."
