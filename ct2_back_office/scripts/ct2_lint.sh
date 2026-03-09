#!/usr/bin/env bash
set -euo pipefail

ct2_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

while IFS= read -r -d '' ct2_file; do
  php -l "$ct2_file" >/dev/null
  printf 'lint ok: %s\n' "$ct2_file"
done < <(find "$ct2_root" -type f -name '*.php' -print0 | sort -z)
