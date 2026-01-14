#!/bin/bash

# Home Route Benchmark Scenario
# Tests the main home page route

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$BASE_DIR"

./benchmarks/run-benchmarks.sh \
  --url "https://forge-v3.test/" \
  --rounds 10 \
  --requests 20000 \
  --concurrency 100 \
  --scenario "home-route"
