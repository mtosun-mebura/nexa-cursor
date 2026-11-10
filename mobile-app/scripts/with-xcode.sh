#!/usr/bin/env bash
# Zorgt dat simctl/xcodebuild Xcode gebruiken i.p.v. alleen Command Line Tools.
set -euo pipefail
XCODE_DEV="/Applications/Xcode.app/Contents/Developer"
if [[ -d "$XCODE_DEV" ]]; then
  export DEVELOPER_DIR="$XCODE_DEV"
fi
exec "$@"
