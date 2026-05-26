#!/usr/bin/env bash
# Eenmalig op AWS Lightsail (Ubuntu): Docker Engine + Compose v2 plugin (`docker compose`).
# Gebruik: bash deploy/install-docker-compose-v2.sh
# Daarna: docker compose version  (met spatie — geen docker-compose v1 nodig op prod)
set -euo pipefail

if docker compose version >/dev/null 2>&1; then
  echo "OK: $(docker compose version)"
  exit 0
fi

echo "==> Docker Compose v2 ontbreekt — installeren..."

if ! command -v docker >/dev/null 2>&1; then
  echo "==> Docker CLI ontbreekt — docker.io installeren"
  sudo apt-get update
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y docker.io
  sudo systemctl enable --now docker
fi

echo "==> Compose v2 plugin (docker-compose-plugin)"
sudo apt-get update
if sudo DEBIAN_FRONTEND=noninteractive apt-get install -y docker-compose-plugin; then
  :
else
  echo "==> docker-compose-plugin niet in apt — Docker officiële repository toevoegen"
  sudo apt-get install -y ca-certificates curl gnupg
  sudo install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
  sudo chmod a+r /etc/apt/keyrings/docker.gpg
  . /etc/os-release
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" \
    | sudo tee /etc/apt/sources.list.d/docker.list >/dev/null
  sudo apt-get update
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
  sudo systemctl enable --now docker
fi

TARGET_USER="${SUDO_USER:-${USER:-ubuntu}}"
if id "$TARGET_USER" >/dev/null 2>&1; then
  sudo usermod -aG docker "$TARGET_USER" || true
  echo "==> User $TARGET_USER toegevoegd aan groep docker (opnieuw inloggen als dat nieuw is)"
fi

if docker compose version >/dev/null 2>&1; then
  echo "OK: $(docker compose version)"
  exit 0
fi

echo "ERROR: docker compose werkt nog niet. Log uit en in, of run: newgrp docker" >&2
exit 1
