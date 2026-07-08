#!/usr/bin/env bash
# Eenmalig op de server (als root) als GitHub Actions meldt:
#   Permission to read ... /home/mtosun/actions-runner/_work ... Access to /home is denied
#
# Oorzaak: de runner-service draait als een andere user dan de eigenaar van _work,
# of /home en/of /home/mtosun is niet doorzoekbaar (geen x voor anderen).
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

detect_runner_service_user() {
  local unit user
  unit="$(systemctl list-units 'actions.runner.*' --all --no-legend --no-pager 2>/dev/null | awk 'NR==1 {print $1}')"
  if [[ -z "$unit" ]]; then
    return 0
  fi
  user="$(systemctl show "$unit" -p User --value 2>/dev/null || true)"
  if [[ -n "$user" && "$user" != "0" ]]; then
    echo "$user"
  fi
}

SERVICE_USER="$(detect_runner_service_user || true)"
if [[ -n "$SERVICE_USER" && "$SERVICE_USER" != "$RUNNER_USER" ]]; then
  echo "==> Runner systemd User=$SERVICE_USER (eigenaar _work is $RUNNER_USER)"
  echo "    Optie 1: rechten fixen (onderstaand)"
  echo "    Optie 2: service als $RUNNER_USER laten draaien (User=$RUNNER_USER in unit)"
fi

echo "==> Traverse-rechten op /home"
if [[ -d /home ]]; then
  chmod 711 /home
fi

echo "==> Traverse-rechten op homedir $RUNNER_HOME"
chmod 711 "$RUNNER_HOME"
if [[ -d "$RUNNER_DIR" ]]; then
  chown -R "$RUNNER_USER:$RUNNER_USER" "$RUNNER_DIR"
  chmod -R u+rwX,g+rX,o+rX "$RUNNER_DIR"
  chmod 711 "$RUNNER_DIR"
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
RUNNER_UNITS=()
while IFS= read -r unit; do
  [[ -n "$unit" ]] || continue
  RUNNER_UNITS+=("$unit")
  echo "--- $unit ---"
  systemctl show "$unit" -p User,Group,FragmentPath,ActiveState,SubState --no-pager
done < <(systemctl list-units 'actions.runner.*' --all --no-legend --no-pager 2>/dev/null | awk '{print $1}')

if [[ ${#RUNNER_UNITS[@]} -gt 0 ]]; then
  echo ""
  echo "==> Runner-service herstarten"
  for unit in "${RUNNER_UNITS[@]}"; do
    systemctl restart "$unit" || systemctl start "$unit" || true
    sleep 1
    state="$(systemctl show "$unit" -p ActiveState --value 2>/dev/null || echo unknown)"
    echo "    $unit → $state"
    if [[ "$state" != "active" ]]; then
      echo "    Log (laatste regels):" >&2
      journalctl -u "$unit" -n 15 --no-pager >&2 || true
    fi
  done
fi

echo ""
echo "Klaar."
echo ""
if [[ -n "$SERVICE_USER" ]]; then
  echo "Test als runner-service user ($SERVICE_USER):"
  echo "  sudo -u $SERVICE_USER test -x /home && sudo -u $SERVICE_USER test -r $RUNNER_DIR/_work && echo OK || echo MISLUKT"
fi
echo "Test als $RUNNER_USER:"
echo "  sudo -u $RUNNER_USER test -r $RUNNER_DIR/_work && echo OK || echo MISLUKT"
echo ""
echo "Tip: deploy-saas.yml gebruikt geen checkout; deploy draait via TENANT_DIR git."
