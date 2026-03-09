#!/usr/bin/env bash

set -euo pipefail

CT2_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CT2_APP_DIR="$CT2_ROOT_DIR/ct2_back_office"
CT2_BASE_URL="http://127.0.0.1:8098/ct2_index.php"
CT2_API_BASE_URL="http://127.0.0.1:8098/api"
CT2_COOKIE_JAR="$(mktemp)"
CT2_TMP_DIR="$(mktemp -d)"
CT2_SERVER_LOG="$CT2_TMP_DIR/ct2_php_server.log"
CT2_SERVER_PID=""
CT2_ITERATIONS=5
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
    printf '[ct2-load] %s\n' "$1"
}

fail() {
    printf '[ct2-load] ERROR: %s\n' "$1" >&2
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
    php -S 127.0.0.1:8098 -t "$CT2_APP_DIR" >"$CT2_SERVER_LOG" 2>&1 &
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

record_stat() {
    local label="$1"
    local output_file="$2"
    local measured="$3"

    assert_seconds_within_limit "$measured" "$label"
    printf '%s\n' "$measured" >> "$output_file"
}

summarize_stat() {
    local label="$1"
    local input_file="$2"

    php -r '
        $label = $argv[1];
        $lines = file($argv[2], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false || $lines === []) {
            fwrite(STDERR, "No timing samples found for {$label}\n");
            exit(1);
        }
        $values = array_map("floatval", $lines);
        sort($values);
        $count = count($values);
        $sum = array_sum($values);
        $avg = $sum / $count;
        $min = $values[0];
        $max = $values[$count - 1];
        printf("%s count=%d avg=%.6fs min=%.6fs max=%.6fs\n", $label, $count, $avg, $min, $max);
    ' "$label" "$input_file"
}

login_seeded_admin() {
    local headers_file="$CT2_TMP_DIR/login_headers.txt"
    local body_file="$CT2_TMP_DIR/login_body.txt"
    local csrf_token=""

    curl -sS -b "$CT2_COOKIE_JAR" -c "$CT2_COOKIE_JAR" -D "$headers_file" -o "$body_file" "$CT2_BASE_URL?module=auth&action=login" >/dev/null
    csrf_token="$(extract_csrf "$body_file")"

    measure_post_form_follow "$CT2_BASE_URL?module=auth&action=login" "$body_file" "$headers_file" \
        --data-urlencode "ct2_csrf_token=$csrf_token" \
        --data-urlencode "username=ct2admin" \
        --data-urlencode "password=ChangeMe123!" >/dev/null

    assert_file_contains "$body_file" "Back-Office Dashboard" "Seeded admin login did not reach the dashboard"
}

measure_login_submit_once() {
    local iteration="$1"
    local cookie_jar="$CT2_TMP_DIR/login_submit_${iteration}.cookies"
    local headers_file="$CT2_TMP_DIR/login_submit_${iteration}.headers"
    local body_file="$CT2_TMP_DIR/login_submit_${iteration}.body"
    local csrf_token=""
    local measured=""

    curl -sS -b "$cookie_jar" -c "$cookie_jar" -D "$headers_file" -o "$body_file" "$CT2_BASE_URL?module=auth&action=login" >/dev/null
    csrf_token="$(extract_csrf "$body_file")"
    measured="$(curl -sS -L -b "$cookie_jar" -c "$cookie_jar" -D "$headers_file" -o "$body_file" -w '%{time_total}' \
        "$CT2_BASE_URL?module=auth&action=login" \
        --data-urlencode "ct2_csrf_token=$csrf_token" \
        --data-urlencode "username=ct2admin" \
        --data-urlencode "password=ChangeMe123!")"

    assert_file_contains "$body_file" "Back-Office Dashboard" "Repeated login submit did not reach the dashboard"
    rm -f "$cookie_jar"
    printf '%s\n' "$measured"
}

log "Starting local CT2 PHP server."
start_server

CT2_HEADERS="$CT2_TMP_DIR/headers.txt"
CT2_BODY="$CT2_TMP_DIR/body.txt"

declare -A CT2_STAT_FILES=(
    [login_get]="$CT2_TMP_DIR/login_get.txt"
    [login_post]="$CT2_TMP_DIR/login_post.txt"
    [dashboard_get]="$CT2_TMP_DIR/dashboard_get.txt"
    [agents_filtered_get]="$CT2_TMP_DIR/agents_filtered_get.txt"
    [module_status_api_get]="$CT2_TMP_DIR/module_status_api_get.txt"
    [financial_export_metadata_get]="$CT2_TMP_DIR/financial_export_metadata_get.txt"
)

log "Sampling repeated request timings."

for iteration in $(seq 1 "$CT2_ITERATIONS"); do
    record_stat "Login page GET" "${CT2_STAT_FILES[login_get]}" \
        "$(measure_get "$CT2_BASE_URL?module=auth&action=login" "$CT2_BODY" "$CT2_HEADERS")"
    assert_file_contains "$CT2_BODY" "CT2 Back-Office Login" "Login page did not render during repeated sampling"

    record_stat "Login submit POST" "${CT2_STAT_FILES[login_post]}" "$(measure_login_submit_once "$iteration")"
done

login_seeded_admin

for iteration in $(seq 1 "$CT2_ITERATIONS"); do
    record_stat "Dashboard GET" "${CT2_STAT_FILES[dashboard_get]}" \
        "$(measure_get "$CT2_BASE_URL?module=dashboard&action=index" "$CT2_BODY" "$CT2_HEADERS")"
    assert_file_contains "$CT2_BODY" "Back-Office Dashboard" "Dashboard route did not render during repeated sampling"

    record_stat "Agents filtered GET" "${CT2_STAT_FILES[agents_filtered_get]}" \
        "$(measure_get "$CT2_BASE_URL?module=agents&action=index&search=AGT-CT2-001" "$CT2_BODY" "$CT2_HEADERS")"
    assert_file_contains "$CT2_BODY" "AGT-CT2-001" "Agents filtered route did not render the seeded record during repeated sampling"

    record_stat "Module status API GET" "${CT2_STAT_FILES[module_status_api_get]}" \
        "$(measure_get "$CT2_API_BASE_URL/ct2_module_status.php" "$CT2_BODY" "$CT2_HEADERS")"
    assert_file_contains "$CT2_BODY" '"success":true' "Module status API did not return a success payload during repeated sampling"

    record_stat "Financial export metadata GET" "${CT2_STAT_FILES[financial_export_metadata_get]}" \
        "$(measure_get "$CT2_API_BASE_URL/ct2_financial_exports.php?ct2_report_run_id=1&source_module=suppliers" "$CT2_BODY" "$CT2_HEADERS")"
    assert_file_contains "$CT2_BODY" '"success":true' "Financial export metadata API did not return a success payload during repeated sampling"
done

log "Repeated timing summary:"
summarize_stat "login_get" "${CT2_STAT_FILES[login_get]}"
summarize_stat "login_post" "${CT2_STAT_FILES[login_post]}"
summarize_stat "dashboard_get" "${CT2_STAT_FILES[dashboard_get]}"
summarize_stat "agents_filtered_get" "${CT2_STAT_FILES[agents_filtered_get]}"
summarize_stat "module_status_api_get" "${CT2_STAT_FILES[module_status_api_get]}"
summarize_stat "financial_export_metadata_get" "${CT2_STAT_FILES[financial_export_metadata_get]}"

log "CT2 load profile checks passed."
