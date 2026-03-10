#!/usr/bin/env bash

set -euo pipefail

ct2_app_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

php "$ct2_app_root/scripts/ct2_nfr_sanity_check.php"
