#!/bin/bash

# Run All Benchmark Scenarios
# Executes all available benchmark scenarios and generates a summary

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$BASE_DIR"

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Running All Benchmark Scenarios${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Array to store results
declare -a SCENARIO_RESULTS

# Run home route benchmark
echo -e "${YELLOW}Running Home Route Benchmark...${NC}"
if ./benchmarks/scenarios/home-route.sh; then
  echo -e "${GREEN}Home Route Benchmark Complete${NC}"
else
  echo -e "${RED}Home Route Benchmark Failed${NC}"
fi
echo ""

# Run API route benchmark
echo -e "${YELLOW}Running API Route Benchmark...${NC}"
if ./benchmarks/scenarios/api-route.sh; then
  echo -e "${GREEN}API Route Benchmark Complete${NC}"
else
  echo -e "${RED}API Route Benchmark Failed${NC}"
fi
echo ""

# Generate summary
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Benchmark Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo "All benchmarks completed. Check individual scenario results in:"
echo "  benchmarks/results/home-route/"
echo "  benchmarks/results/api-route/"
echo ""
