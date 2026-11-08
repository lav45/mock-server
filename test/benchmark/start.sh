#!/usr/bin/env sh
#
# Finds the peak throughput of the mock server.
#
# The CPU cores are split: `wrk` gets the last CLIENT_CORES cores with one thread per core, the
# server gets the rest, one `amphp/cluster` worker per core. Neither competes with the other for a
# core. One core is not enough for `wrk`: the kernel TCP work of the client runs on its cores too,
# and with a single core the client saturates while the server still has 30% of its CPU idle.
#
#   test/benchmark/start.sh                     # mock-server:server
#   test/benchmark/start.sh lav45/mock-server:4.8

set -e

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
ROOT_DIR=$(cd "$SCRIPT_DIR/../.." && pwd)

IMAGE=${1:-mock-server:server}

CLIENT_CORES=2

CORES=$(nproc)
if [ "$CORES" -le "$CLIENT_CORES" ]; then
  echo "More than $CLIENT_CORES CPU cores are required: $CLIENT_CORES for wrk, the rest for the server" >&2
  exit 1
fi

SERVER_WORKERS=$((CORES - CLIENT_CORES))
SERVER_CPUS="0-$((SERVER_WORKERS - 1))"
CLIENT_CPUS="$SERVER_WORKERS-$((CORES - 1))"

WARMUP_DURATION=15s
STEP_DURATION=15s
STEP_CONNECTIONS="16 32 64 128 256 512 1024"
# CPU usage, in percent of the cores given, above which a side is considered saturated.
SATURATION_PERCENT=90

# `docker rm -f` kills and removes in one synchronous call: after `docker kill` a `--rm` container
# is already being removed, so a separate `docker rm` fails and its name stays taken for a moment.
RUNNING_PROCESS=$(
  docker ps -a --filter name='(test_mock_server|test_runner)' --format "{{.Names}}"
)

for PROCESS in $RUNNING_PROCESS ; do
  docker rm -f "$PROCESS" > /dev/null
done

cleanup() {
  docker rm -f test_runner > /dev/null 2>&1 || true
  docker rm -f test_mock_server > /dev/null 2>&1 || true
}
trap cleanup EXIT

# `amphp/cluster` counts workers by `getconf _NPROCESSORS_ONLN`, which ignores CPU affinity,
# so the worker count is passed explicitly.
docker run -d --rm \
  -v "$ROOT_DIR":/app:ro \
  -v "$SCRIPT_DIR":/app/mocks:ro \
  -e CONFIG_PATH=/app/mocks/config.yaml \
  --ulimit nofile=65536 \
  --name test_mock_server \
  "$IMAGE" \
  vendor/bin/cluster -w "$SERVER_WORKERS" --pid-file /run/webhook/cluster.pid --log error bin/start > /dev/null

getIp() {
  docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$1"
}

# Container IPs are not reachable from the host under rootless Docker,
# so the readiness check runs inside a container.
waitHttp() {
  docker run --rm mock-server:tool sh -c '
    for ATTEMPT in $(seq 30); do
      wget -q -S -O /dev/null "$1" 2>&1 | grep -q "HTTP/" && exit 0
      sleep 1
    done
    exit 1
  ' sh "$1"
}

URL="http://$(getIp "test_mock_server")"

if ! waitHttp "$URL"; then
  echo "Mock server did not become ready in time" >&2
  exit 1
fi

# Rootless Docker has no cpuset controller and drops `--cpuset-cpus`, so cores are pinned with
# `taskset`. The server image has no `taskset`: a tool container joins the server's PID namespace
# and pins every process of the server once all cluster workers are up.
docker run --rm \
  --pid container:test_mock_server \
  mock-server:tool \
  sh -c '
    # A PID may be gone by the time it is pinned (the `ls` and `grep` of this loop), so a single
    # failure is skipped, and the result is checked on every process still alive afterwards.
    for PID in $(ls /proc | grep -E "^[0-9]+$"); do
      taskset -a -p -c "$1" "$PID" > /dev/null 2>&1 || true
    done
    for PID in $(ls /proc | grep -E "^[0-9]+$"); do
      AFFINITY=$(taskset -c -p "$PID" 2>/dev/null) || continue
      if [ "${AFFINITY##*: }" != "$1" ] && [ "$PID" != "$$" ]; then
        echo "Server process $PID is not pinned: $AFFINITY" >&2
        exit 1
      fi
    done
  ' sh "$SERVER_CPUS"

# Runs wrk on its cores and prints its report, then the CPU usage of the server and of the
# client, in percent of the cores each was given, sampled halfway through the run.
runWrk() {
  CONNECTIONS=$1
  DURATION=$2

  docker run -d \
    --ulimit nofile=65536 \
    --name test_runner \
    mock-server:tool \
    taskset -c "$CLIENT_CPUS" wrk -t "$CLIENT_CORES" -c "$CONNECTIONS" -d "$DURATION" --latency "$URL" > /dev/null

  SECONDS_TOTAL=${DURATION%s}
  sleep $((SECONDS_TOTAL / 2))
  STATS=$(docker stats --no-stream --format '{{.Name}} {{.CPUPerc}}' test_mock_server test_runner)

  docker wait test_runner > /dev/null
  docker logs test_runner 2>&1
  docker rm test_runner > /dev/null

  echo "$STATS" | awk -v server="$SERVER_WORKERS" -v client="$CLIENT_CORES" '
    { sub("%", "", $2) }
    $1 == "test_mock_server" { printf "server-cpu: %.0f%%\n", $2 / server }
    $1 == "test_runner" { printf "client-cpu: %.0f%%\n", $2 / client }
  '
}

isSaturated() {
  [ "${1%\%}" -ge "$SATURATION_PERCENT" ]
}

echo "Server: $SERVER_WORKERS workers on CPUs $SERVER_CPUS; wrk: $CLIENT_CORES threads on CPUs $CLIENT_CPUS"
echo "Warm-up: $WARMUP_DURATION"

runWrk 256 "$WARMUP_DURATION" > /dev/null

printf '\n%12s %12s %10s %10s %8s %11s %11s %s\n' connections requests/sec p50 p99 errors server-cpu wrk-cpu ""

BEST_RPS=0
BEST_CONNECTIONS=0
BEST_MARK=""

for CONNECTIONS in $STEP_CONNECTIONS ; do
  REPORT=$(runWrk "$CONNECTIONS" "$STEP_DURATION")

  RPS=$(echo "$REPORT" | awk '/^Requests\/sec:/ { print $2 }')
  P50=$(echo "$REPORT" | awk '$1 == "50%" { print $2 }')
  P99=$(echo "$REPORT" | awk '$1 == "99%" { print $2 }')
  ERRORS=$(echo "$REPORT" | awk '
    /Socket errors:/ { gsub(",", ""); sum += $4 + $6 + $8 + $10 }
    /Non-2xx or 3xx responses:/ { sum += $NF }
    END { print sum + 0 }
  ')
  SERVER_CPU=$(echo "$REPORT" | awk '/^server-cpu:/ { print $2 }')
  CLIENT_CPU=$(echo "$REPORT" | awk '/^client-cpu:/ { print $2 }')

  MARK=""
  if isSaturated "$CLIENT_CPU"; then
    MARK="wrk saturated"
  elif isSaturated "$SERVER_CPU"; then
    MARK="server saturated"
  fi

  printf '%12s %12s %10s %10s %8s %11s %11s %s\n' \
    "$CONNECTIONS" "$RPS" "$P50" "$P99" "$ERRORS" "$SERVER_CPU" "$CLIENT_CPU" "$MARK"

  if [ "$(echo "$RPS $BEST_RPS" | awk '{ print ($1 > $2) }')" -eq 1 ]; then
    BEST_RPS=$RPS
    BEST_CONNECTIONS=$CONNECTIONS
    BEST_MARK=$MARK
  fi
done

echo
echo "Peak: $BEST_RPS requests/sec at $BEST_CONNECTIONS connections"

if [ "$BEST_MARK" = "wrk saturated" ]; then
  echo "Warning: wrk was saturated at the peak, so it may be set by the client, not the server" >&2
fi
