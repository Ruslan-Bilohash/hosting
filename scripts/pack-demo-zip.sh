#!/usr/bin/env bash
# Production demo ZIP for GitHub Release / Packages (30-day MySQL demo).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
VER="${1:-dev}"
OUT="dist/hosting-cms-demo-30d-${VER}.zip"
mkdir -p dist
rm -f "$OUT"
zip -rq "$OUT" . \
  -x ".git/*" \
  -x "screenshot/*" \
  -x "scripts/node_modules/*" \
  -x "scripts/deploy.config.local.ps1" \
  -x "scripts/ssh-*" \
  -x "config.local.php" \
  -x "data/*.json" \
  -x "data/logs/*" \
  -x "data/db.config.php" \
  -x "data/admin.config.php" \
  -x "data/mysql-provision.config.php" \
  -x "data/ssh.config.local.php" \
  -x "data/pma.config.php" \
  -x "data/installed.lock" \
  -x "public_html/*" \
  -x "pma/vendor/*" \
  -x "pma/js/*" \
  -x "pma/templates/*" \
  -x "dist/*" \
  -x "_hosting-deploy.zip" \
  -x "release-payload.json"
echo "Created $OUT ($(du -h "$OUT" | cut -f1))"