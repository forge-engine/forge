# Forge Framework Benchmark Testing

This directory contains benchmark testing infrastructure for measuring Forge framework performance using Apache Bench (ab).

## Overview

The benchmark system uses Apache Bench to test HTTP performance against the live domain `https://forge-v3.test/`. It follows the same methodology as the Trongate benchmark study:

- **10 rounds** of testing per scenario
- **20,000 requests** per round
- **Best result** selected from all rounds (highest requests/second)
- Results saved in both JSON and human-readable formats

## Prerequisites

### 1. Install Apache Bench

**macOS:**
```bash
brew install httpd
```

**Linux:**
```bash
sudo apt-get install apache2-utils
```

**Verify installation:**
```bash
ab -V
```

### 2. Configure Herd

Ensure your local domain is configured:
- Domain: `forge-v3.test`
- SSL certificate configured
- Application accessible at `https://forge-v3.test/`

### 3. Application Configuration

Before running benchmarks, ensure:
- Debug mode: **OFF**
- Environment: **production**
- Caching: Enabled (if applicable)
- Database: Connected and optimized

## Usage

### Run Single Scenario

**Home Route:**
```bash
./benchmarks/scenarios/home-route.sh
```

**API Route:**
```bash
./benchmarks/scenarios/api-route.sh
```

### Run All Scenarios

```bash
./benchmarks/scenarios/all-scenarios.sh
```

### Custom Benchmark

```bash
./benchmarks/run-benchmarks.sh \
  --url "https://forge-v3.test/" \
  --rounds 10 \
  --requests 20000 \
  --concurrency 100 \
  --scenario "custom-test"
```

## Command Line Options

The main benchmark script (`run-benchmarks.sh`) accepts the following options:

- `--url URL` - URL to benchmark (required)
- `--rounds N` - Number of rounds to run (default: 10)
- `--requests N` - Number of requests per round (default: 20000)
- `--concurrency N` - Number of concurrent requests (default: 100)
- `--scenario NAME` - Scenario name for results (default: benchmark)
- `--help` - Show help message

## Test Scenarios

### 1. Home Route (`home-route.sh`)

- **URL**: `https://forge-v3.test/`
- **Description**: Tests the main home page with view rendering and database query
- **Use case**: Baseline performance measurement

### 2. API Route (`api-route.sh`)

- **URL**: `https://forge-v3.test/examples/raw-sql`
- **Description**: Tests JSON API endpoint with database queries
- **Use case**: API performance measurement

## Results

Results are saved in `benchmarks/results/[scenario-name]/` with the following files:

- `results-YYYYMMDD_HHMMSS.json` - JSON format with all metrics
- `results-YYYYMMDD_HHMMSS.txt` - Human-readable text format
- `round-N.txt` - Raw Apache Bench output for each round
- `round-N.dat` - GNUplot data file for visualization

### Key Metrics

- **Requests per second (RPS)** - Primary performance metric
- **Time per request (mean)** - Average response time
- **Time per request (mean, concurrent)** - Average across all concurrent requests
- **Transfer rate** - Data transfer speed
- **Failed requests** - Number of failed requests
- **Total time** - Total time for all requests

## Interpreting Results

### Requests per Second (RPS)

Higher is better. This is the primary metric for comparing frameworks.

**Example:**
- 1,000 req/s = Can handle 1,000 requests per second
- 5,000 req/s = Can handle 5,000 requests per second

### Time per Request

Lower is better. Indicates how fast each request is processed.

**Example:**
- 1.0 ms = Very fast
- 10.0 ms = Fast
- 100.0 ms = Moderate
- 1000.0 ms = Slow

### Failed Requests

Should be 0. Any failed requests indicate issues.

## Best Practices

1. **Run when system is idle** - Close other applications to get accurate results
2. **Warm up first** - Consider running a small warm-up before the actual benchmark
3. **Multiple runs** - Run benchmarks multiple times to ensure consistency
4. **Compare conditions** - Test with same conditions as other frameworks
5. **Document environment** - Note your system specs (CPU, RAM, OS, PHP version)

## Comparison with Other Frameworks

When comparing Forge with other frameworks:

1. Use the same methodology (10 rounds, 20,000 requests)
2. Test on the same machine
3. Use the same web server (Apache/Nginx)
4. Use the same PHP version
5. Ensure all frameworks are in production mode
6. Test the same type of routes (home page, API endpoint)

## Troubleshooting

### Apache Bench not found

```bash
# macOS
brew install httpd

# Linux
sudo apt-get install apache2-utils
```

### URL not accessible

- Verify Herd is running: `herd status`
- Check domain configuration: `herd links`
- Test manually: `curl https://forge-v3.test/`

### Low performance results

- Ensure debug mode is OFF
- Check application is in production mode
- Verify database is optimized
- Check system resources (CPU, RAM)
- Close other applications

### Failed requests

- Check application logs
- Verify database connection
- Check web server configuration
- Ensure SSL certificate is valid

## Example Output

```
========================================
Forge Framework Benchmark
========================================

URL: https://forge-v3.test/
Rounds: 10
Requests per round: 20,000
Concurrency: 100
Scenario: home-route

Verifying URL accessibility...
URL is accessible

Starting benchmark rounds...
Round 1/10...
  Round 1: 1234.56 req/s
Round 2/10...
  Round 2: 1256.78 req/s
...

========================================
Benchmark Complete!
========================================

Best Result (Round 3):
  Requests/sec: 1,256.78

Results saved to:
  JSON: benchmarks/results/home-route/results-20250113_120000.json
  Text: benchmarks/results/home-route/results-20250113_120000.txt
```

## License

Part of the Forge Framework project.
