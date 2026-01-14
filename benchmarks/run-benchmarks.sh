#!/bin/bash

# Benchmark Testing Script for Forge Framework
# Uses Apache Bench (ab) to measure HTTP performance

# Default values
URL=""
ROUNDS=10
REQUESTS=20000
CONCURRENCY=100
SCENARIO_NAME="benchmark"
RESULTS_DIR="benchmarks/results"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Parse command line arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --url)
      URL="$2"
      shift 2
      ;;
    --rounds)
      ROUNDS="$2"
      shift 2
      ;;
    --requests)
      REQUESTS="$2"
      shift 2
      ;;
    --concurrency)
      CONCURRENCY="$2"
      shift 2
      ;;
    --scenario)
      SCENARIO_NAME="$2"
      shift 2
      ;;
    --help)
      echo "Usage: $0 [OPTIONS]"
      echo ""
      echo "Options:"
      echo "  --url URL           URL to benchmark (required)"
      echo "  --rounds N           Number of rounds to run (default: 10)"
      echo "  --requests N        Number of requests per round (default: 20000)"
      echo "  --concurrency N     Number of concurrent requests (default: 100)"
      echo "  --scenario NAME     Scenario name for results (default: benchmark)"
      echo "  --help              Show this help message"
      exit 0
      ;;
    *)
      echo "Unknown option: $1"
      echo "Use --help for usage information"
      exit 1
      ;;
  esac
done

# Validate required parameters
if [ -z "$URL" ]; then
  echo -e "${RED}Error: --url is required${NC}"
  echo "Use --help for usage information"
  exit 1
fi

# Check if Apache Bench is installed
if ! command -v ab &> /dev/null; then
  echo -e "${RED}Error: Apache Bench (ab) is not installed${NC}"
  echo ""
  echo "Installation:"
  echo "  macOS:   brew install httpd"
  echo "  Linux:   sudo apt-get install apache2-utils"
  exit 1
fi

# Create results directory
RESULTS_DIR="$BASE_DIR/$RESULTS_DIR"
mkdir -p "$RESULTS_DIR"

# Create scenario-specific directory
SCENARIO_DIR="$RESULTS_DIR/$SCENARIO_NAME"
mkdir -p "$SCENARIO_DIR"

# Timestamp for this benchmark run
TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
TIMESTAMP_FILE=$(date -u +"%Y%m%d_%H%M%S")

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Forge Framework Benchmark${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo "URL: $URL"
echo "Rounds: $ROUNDS"
echo "Requests per round: $REQUESTS"
echo "Concurrency: $CONCURRENCY"
echo "Scenario: $SCENARIO_NAME"
echo ""

# Verify URL is accessible
echo -e "${YELLOW}Verifying URL accessibility...${NC}"
# Try with -k to ignore SSL certificate issues (common with local dev)
# Use GET instead of HEAD as some servers don't handle HEAD properly
HTTP_CODE=$(curl -s -k -o /dev/null -w "%{http_code}" --max-time 5 "$URL" 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "000" ] || [ "$HTTP_CODE" -ge 400 ]; then
  echo -e "${RED}Error: URL is not accessible: $URL${NC}"
  echo "HTTP Status Code: $HTTP_CODE"
  echo "Please verify:"
  echo "  1. Herd is running: herd status"
  echo "  2. Domain is linked: herd links"
  echo "  3. Application is accessible in browser"
  exit 1
fi
echo -e "${GREEN}URL is accessible (HTTP $HTTP_CODE)${NC}"
echo ""

# Arrays to store results
declare -a ROUND_RESULTS
declare -a RPS_VALUES
BEST_ROUND=0
BEST_RPS=0

# Run benchmark rounds
echo -e "${BLUE}Starting benchmark rounds...${NC}"
for ((round=1; round<=ROUNDS; round++)); do
  echo -e "${YELLOW}Round $round/$ROUNDS...${NC}"

  # Run Apache Bench
  OUTPUT_FILE="$SCENARIO_DIR/round-${round}.txt"
  GNUPLOT_FILE="$SCENARIO_DIR/round-${round}.dat"

  ab -n "$REQUESTS" \
     -c "$CONCURRENCY" \
     -k \
     -g "$GNUPLOT_FILE" \
     -q \
     "$URL" > "$OUTPUT_FILE" 2>&1

  # Parse results from ab output
  RPS=$(grep "Requests per second" "$OUTPUT_FILE" | awk '{print $4}')
  TIME_PER_REQUEST=$(grep "Time per request" "$OUTPUT_FILE" | head -1 | awk '{print $4}')
  TIME_PER_REQUEST_CONCURRENT=$(grep "Time per request" "$OUTPUT_FILE" | tail -1 | awk '{print $4}')
  TRANSFER_RATE=$(grep "Transfer rate" "$OUTPUT_FILE" | awk '{print $3}')
  FAILED_REQUESTS=$(grep "Failed requests" "$OUTPUT_FILE" | awk '{print $3}' | head -1)
  TOTAL_TIME=$(grep "Time taken for tests" "$OUTPUT_FILE" | awk '{print $5}')

  # Clean up values (remove units)
  RPS=${RPS//[^0-9.]/}
  TIME_PER_REQUEST=${TIME_PER_REQUEST//[^0-9.]/}
  TIME_PER_REQUEST_CONCURRENT=${TIME_PER_REQUEST_CONCURRENT//[^0-9.]/}
  TRANSFER_RATE=${TRANSFER_RATE//[^0-9.]/}
  FAILED_REQUESTS=${FAILED_REQUESTS:-0}
  TOTAL_TIME=${TOTAL_TIME//[^0-9.]/}

  # Store results
  ROUND_RESULTS+=("{\"round\":$round,\"requests_per_second\":$RPS,\"time_per_request_mean\":$TIME_PER_REQUEST,\"time_per_request_mean_concurrent\":$TIME_PER_REQUEST_CONCURRENT,\"transfer_rate\":$TRANSFER_RATE,\"failed_requests\":$FAILED_REQUESTS,\"total_time\":$TOTAL_TIME}")
  RPS_VALUES+=("$RPS")

  # Track best result (compare as floating point numbers)
  if command -v bc &> /dev/null; then
    if (( $(echo "$RPS > $BEST_RPS" | bc -l) )); then
      BEST_RPS=$RPS
      BEST_ROUND=$round
    fi
  else
    # Fallback: use awk for floating point comparison
    COMPARE=$(awk "BEGIN {if ($RPS > $BEST_RPS) print 1; else print 0}")
    if [ "$COMPARE" = "1" ]; then
      BEST_RPS=$RPS
      BEST_ROUND=$round
    fi
  fi

  echo -e "  ${GREEN}Round $round: ${RPS} req/s${NC}"
done

echo ""

# Extract best result details
BEST_RESULT_JSON=${ROUND_RESULTS[$((BEST_ROUND-1))]}

# Generate JSON report
JSON_FILE="$SCENARIO_DIR/results-${TIMESTAMP_FILE}.json"
{
  echo "{"
  echo "  \"scenario\": \"$SCENARIO_NAME\","
  echo "  \"url\": \"$URL\","
  echo "  \"rounds\": $ROUNDS,"
  echo "  \"requests_per_round\": $REQUESTS,"
  echo "  \"concurrency\": $CONCURRENCY,"
  echo "  \"best_result\": $BEST_RESULT_JSON,"
  echo "  \"all_rounds\": ["
  for ((i=0; i<${#ROUND_RESULTS[@]}; i++)); do
    if [ $i -eq $((${#ROUND_RESULTS[@]}-1)) ]; then
      echo "    ${ROUND_RESULTS[$i]}"
    else
      echo "    ${ROUND_RESULTS[$i]},"
    fi
  done
  echo "  ],"
  echo "  \"timestamp\": \"$TIMESTAMP\""
  echo "}"
} > "$JSON_FILE"

# Extract values from best result JSON for text report
BEST_TIME_PER_REQUEST=$(echo "$BEST_RESULT_JSON" | grep -o '"time_per_request_mean":[0-9.]*' | cut -d: -f2)
BEST_TIME_PER_REQUEST_CONCURRENT=$(echo "$BEST_RESULT_JSON" | grep -o '"time_per_request_mean_concurrent":[0-9.]*' | cut -d: -f2)
BEST_TRANSFER_RATE=$(echo "$BEST_RESULT_JSON" | grep -o '"transfer_rate":[0-9.]*' | cut -d: -f2)
BEST_FAILED_REQUESTS=$(echo "$BEST_RESULT_JSON" | grep -o '"failed_requests":[0-9]*' | cut -d: -f2)
BEST_TOTAL_TIME=$(echo "$BEST_RESULT_JSON" | grep -o '"total_time":[0-9.]*' | cut -d: -f2)

# Generate human-readable report
TEXT_FILE="$SCENARIO_DIR/results-${TIMESTAMP_FILE}.txt"
cat > "$TEXT_FILE" << EOF
========================================
Benchmark Results: $SCENARIO_NAME
========================================

URL: $URL
Rounds: $ROUNDS
Requests per round: $REQUESTS
Concurrency: $CONCURRENCY
Timestamp: $TIMESTAMP

----------------------------------------
Best Result (Round $BEST_ROUND):
----------------------------------------
  Requests/sec: $(printf "%.2f" $BEST_RPS)
  Time per request: ${BEST_TIME_PER_REQUEST} ms (mean)
  Time per request: ${BEST_TIME_PER_REQUEST_CONCURRENT} ms (mean, across all concurrent)
  Transfer rate: ${BEST_TRANSFER_RATE} Kbytes/sec
  Failed requests: ${BEST_FAILED_REQUESTS}
  Total time: ${BEST_TOTAL_TIME} seconds

----------------------------------------
All Rounds Summary:
----------------------------------------
EOF

# Add summary of all rounds
for ((round=1; round<=ROUNDS; round++)); do
  RPS=${RPS_VALUES[$((round-1))]}
  echo "  Round $round: $(printf "%.2f" $RPS) req/s" >> "$TEXT_FILE"
done

# Display results
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Benchmark Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Best Result (Round $BEST_ROUND):${NC}"
echo -e "  Requests/sec: ${GREEN}$(printf "%.2f" $BEST_RPS)${NC}"
echo ""
echo "Results saved to:"
echo "  JSON: $JSON_FILE"
echo "  Text: $TEXT_FILE"
echo ""
