#!/usr/bin/env bash
# Eenmalig op de server als deploy faalt met git-permissies, bijv.:
#   insufficient permission for adding an object to repository database .git/objects
#   unable to unlink old 'backend/storage/.../.gitignore': Permission denied
#
# Gebruik (als root):
#   sudo bash deploy/fix-git-ownership.sh
#   sudo bash deploy/fix-git-ownership.sh --user mtosun --dir /home/nexasuite.nl/apps/saas/current
set -euo pipefail

DEPLOY_USER="${DEPLOY_USER:-mtosun}"
TENANT_DIR="${TENANT_DIR:-/home/nexasuite.nl/apps/saas/current}"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --user)
      DEPLOY_USER="$2"
      shift 2
      ;;
    --dir)
      TENANT_DIR="$2"
      shift 2
      ;;
    *)
      echo "Onbekend argument: $1" >&2
      exit 1
      ;;
  esac
done

if [[ "$(id -un)" != "root" ]]; then
  echo "Draai als root: sudo bash $0" >&2
  exit 1
fi

if [[ ! -d "$TENANT_DIR/.git" ]]; then
  echo "ERROR: Geen git-repo: $TENANT_DIR/.git" >&2
  exit 1
fi

echo "==> chown -R ${DEPLOY_USER}:${DEPLOY_USER} $TENANT_DIR/.git (inclusief objects/pack)"
chown -R "${DEPLOY_USER}:${DEPLOY_USER}" "$TENANT_DIR/.git"
chmod -R u+rwX "$TENANT_DIR/.git"

alien="$(find "$TENANT_DIR/.git" ! -user "$DEPLOY_USER" -print -quit 2>/dev/null || true)"
if [[ -n "$alien" ]]; then
  echo "ERROR: Nog bestanden met verkeerde eigenaar: $alien" >&2
  exit 1
fi

test_file="$TENANT_DIR/.git/objects/.fix-git-ownership-test"
sudo -u "$DEPLOY_USER" touch "$test_file"
sudo -u "$DEPLOY_USER" rm -f "$test_file"

echo "OK: $DEPLOY_USER kan schrijven in .git/objects"

BACKEND_DIR="${TENANT_DIR}/backend"
for d in "$BACKEND_DIR/storage" "$BACKEND_DIR/bootstrap/cache" "$BACKEND_DIR/public/build" "$BACKEND_DIR/public/frontend-themes"; do
  if [[ -e "$d" ]]; then
    echo "==> chown -R ${DEPLOY_USER}:${DEPLOY_USER} $d"
    chown -R "${DEPLOY_USER}:${DEPLOY_USER}" "$d"
    chmod -R ug+rwX "$d"
  fi
done

echo "OK: git + Laravel writable dirs zijn teruggezet naar ${DEPLOY_USER}"
echo "Herstart daarna de GitHub Actions workflow (deploy-prod of deploy-saas)."
