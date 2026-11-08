#!/usr/bin/env bash
#
# Builds the mock-server:tool and mock-server:server images.
# ./build.sh [--no-cache]

set -euxo pipefail

# --no-cache — build without the docker cache: after a failed build or to pick up fresh base image packages; cached by default.
NO_CACHE=()
case "$*" in
  '') ;;
  --no-cache) NO_CACHE=(--no-cache) ;;
  *) echo "build.sh: argument «$*» — only --no-cache is expected" >&2; exit 1 ;;
esac

docker build --progress=plain "${NO_CACHE[@]}" --target tool -t mock-server:tool .

docker build --progress=plain "${NO_CACHE[@]}" --target base-server -t mock-server:server .
