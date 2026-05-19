#!/usr/bin/env bash
# Eenmalig op de server (als root) als GitHub Actions meldt:
#   Permission to read ... /home/mtosun/actions-runner/_work ... Access to /home is denied
#
# Oorzaak: de runner-service draait als een andere user dan de eigenaar van _work,
# of /home/mtosun is niet doorzoekbaar (geen x voor anderen).
#
# Gebruik:
#   sudo bash deploy/fix-runner-home-access.sh
#   sudo bash deploy/fix-runner-home-access.sh --move-work-to /var/lib/nexa-actions/_work
set -euo pipefail

RUNNER_HOME="${RUNNER_HOME:-/home/mtosun}"
RUNNER_DIR="${RUNNER_DIR:-$RUNNER_HOME/actions-runner}"
RUNNER_USER="${RUNNER_USER:-mtosun}"
MOVE_WORK_TO=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --move-work-to)
      MOVE_WORK_TO="$2"
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

echo "==> Traverse-rechten op homedir (anderen mogen mappen doorlopen)"
chmod 711 "$RUNNER_HOME"
if [[ -d "$RUNNER_DIR" ]]; then
  chown -R "$RUNNER_USER:$RUNNER_USER" "$RUNNER_DIR"
  chmod -R u+rwX,g+rX "$RUNNER_DIR"
fi

if [[ -n "$MOVE_WORK_TO" ]]; then
  echo "==> Work folder verplaatsen naar $MOVE_WORK_TO"
  mkdir -p "$MOVE_WORK_TO"
  chown -R "$RUNNER_USER:$RUNNER_USER" "$(dirname "$MOVE_WORK_TO")" "$MOVE_WORK_TO"
  if [[ -f "$RUNNER_DIR/.runner" ]]; then
    if command -v python3 >/dev/null 2>&1; then
      python3 - "$RUNNER_DIR/.runner" "$MOVE_WORK_TO" <<'PY'
import json, sys
path, work = sys.argv[1], sys.argv[2]
with open(path) as f:
    data = json.load(f)
data["workFolder"] = work
with open(path, "w") as f:
    json.dump(data, f, indent=2)
    f.write("\n")
PY
    else
      echo "Waarschuwing: pas workFolder handmatig aan in $RUNNER_DIR/.runner" >&2
    fi
  fi
fi

echo "==> Runner systemd-units (User= moet $RUNNER_USER zijn)"
systemctl list-units 'actions.runner.*' --all --no-pager 2>/dev/null || true
for unit in $(systemctl list-units 'actions.runner.*' --all --no-legend --no-pager 2>/dev/null | awk '{print $1}'); do
  echo "--- $unit ---"
  systemctl show "$unit" -p User,Group,FragmentPath --no-pager
done

echo ""
echo "Klaar. Herstart de runner:"
echo "  sudo systemctl restart 'actions.runner.*'"
echo ""
echo "Test als $RUNNER_USER:"
echo "  sudo -u $RUNNER_USER test -r $RUNNER_DIR/_work && echo OK || echo MISLUKT"
echo ""
echo "Tip: voor deploy-saas.yml is geen _work/checkout meer nodig; deploy gebruikt TENANT_DIR git."
