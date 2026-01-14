#!/bin/bash

# API Route Benchmark Scenario
# Tests the JSON API endpoint with database queries

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$BASE_DIR"

./benchmarks/run-benchmarks.sh \
  --url "https://forge-v3.test/examples/raw-sql" \
  --rounds 10 \
  --requests 20000 \
  --concurrency 100 \
  --scenario "api-route"
