# Load Test Harness — Compliance Hub

k6-based load testing for the four scenarios required by the MSc project
report (Tables 8.7 / 8.8 — T19, T20).

---

## Prerequisites

| Tool | Version | Purpose |
|------|---------|---------|
| [k6](https://k6.io) | ≥ 0.50 | Load test runner |
| PHP ≥ 8.2 + Laravel | — | Application server |
| SQLite (database.sqlite) | — | Backend database |
| Ollama + llava:7b | — | Local inference (L3) |
| ClamAV REST | — | Malware scanning (L2, L3) |

**Install k6 (Windows):**

```powershell
# Option A: Download binary
Invoke-WebRequest -Uri "https://github.com/grafana/k6/releases/download/v0.56.0/k6-v0.56.0-windows-amd64.zip" -OutFile k6.zip
Expand-Archive k6.zip -DestinationPath C:\tools\k6
# Add C:\tools\k6 to PATH

# Option B: Scoop
scoop install k6
```

---

## Authentication bypass

The application uses email OTP as a second factor.  Load-testing the full
OTP flow is impractical (it requires reading Mailpit and entering a 6-digit
code per session).  Instead, a **test-only bypass** is provided:

**Route:** `GET /auto-login?user_id={id}`

**Gate:** The route is behind `LOAD_TEST_BYPASS_ENABLED` in `.env`.
When the flag is absent or `false`, the route returns **403**.  When set to
`true`, it calls `Auth::loginUsingId()` and returns JSON, seeding the session
cookie that k6 carries into subsequent requests.

```
# .env  —  OFF by default, must be explicitly enabled
LOAD_TEST_BYPASS_ENABLED=true
```

**CSRF:** Web routes require a CSRF token.  Each k6 script GETs `/login`
first to extract the token from `<meta name="csrf-token">`, then includes
it as `X-CSRF-TOKEN` on mutating requests.

**Security note:** This bypass is committed to the repo for reproducibility
of the thesis evaluation.  It must be set to `false` (or removed) in any
deployment that is not the local test harness.

---

## Running the scenarios

All commands assume you are in the `tools/loadtest/` directory:

```bash
cd tools/loadtest
```

The application must be running at `BASE_URL` (default `http://localhost:8000`).
Ensure the queue worker is active: `php artisan queue:work`.

### L1 — Dashboard browsing (read-only)

```bash
k6 run --env BASE_URL=http://localhost:8000 L1_dashboard_browsing.js
```

Simulates authenticated users browsing the dashboard and its JSON API
endpoints.  No mutations.  Stages: 1 → 5 → 10 → 0 VUs over 70 s.

### L2 — Concurrent evidence upload

```bash
k6 run --env BASE_URL=http://localhost:8000 L2_concurrent_upload.js
```

Simulates multiple users uploading evidence files simultaneously.
Exercises upload validation, ClamAV scanning, and the analysis queue.
Stages: 1 → 3 → 5 → 0 VUs over 70 s.

### L3 — Queue saturation

```bash
k6 run --env BASE_URL=http://localhost:8000 L3_queue_saturation.js
```

Uploads evidence in bursts while polling `/dashboard/scan-stats` to
measure queue depth.  See [Reading L3 results](#reading-l3-results) below.
Stages: 1 → 3 → 5 → 8 → 3 → 0 VUs over 110 s.

### L4 — Mixed realistic workload

```bash
k6 run --env BASE_URL=http://localhost:8000 L4_mixed_workload.js
```

Combines all three activity types: 60 % reads, 30 % uploads, 10 % queue
polls.  Stages: 1 → 5 → 10 → 5 → 0 VUs over 100 s.

---

## Reading L3 results

L3 is the most analytically important scenario.  The key metrics in
`results/L3_queue_saturation.json` are:

| Field | Meaning |
|-------|---------|
| `queue_peak` | Maximum number of pending (unscanned/unanalysed) items observed during the run |
| `drain_stalled_at_iteration` | The iteration number at which the pending count stopped decreasing — this is the **throughput ceiling** |
| `drain_stalled` | `true` if the queue saturated, `false` if it drained successfully |
| `final_pending` | Queue depth at end of observation window. `-1` if never reached zero |
| `time_to_drain` | `"completed"` or `"did_not_drain"` |

**How to read the throughput ceiling (§8.5):**

1. Run L3 to completion.
2. Open `results/L3_queue_saturation.json`.
3. If `drain_stalled` is `true`, note the `upload_count` at the stall
   point and divide by the elapsed time to that point.  This gives you
   the maximum sustained upload rate the pipeline can handle.
4. If `drain_stalled` is `false`, the pipeline can sustain the tested
   upload rate.  Increase VUs or upload frequency to find the ceiling.
5. The ceiling is bounded by Ollama inference time (~15-30 s per item
   for llava:7b).  The theoretical max is `60 / inference_time_s`
   items per minute for a single inference slot.

---

## Capturing host CPU and memory

k6 measures HTTP-level metrics.  For host-level resource usage, run a
sidecar process that samples CPU and memory during the k6 run.

### Windows (PowerShell)

Open a second terminal **before** starting k6:

```powershell
# Sample every 2 seconds, write to CSV
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$csvPath = "tools/loadtest/results/host-metrics-$timestamp.csv"
"Timestamp,CPU_%,WorkingSet_MB,PrivateBytes_MB" | Out-File $csvPath

while ($true) {
    $ts = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $cpu = (Get-Counter "\Processor(_Total)\% Processor Time").CounterSamples.CookedValue
    $proc = Get-Process -Name php -ErrorAction SilentlyContinue | Measure-Object WorkingSet64 -Sum
    $ws = [math]::Round(($proc.Sum / 1MB), 1)
    $priv = [math]::Round(((Get-Process -Name php -ErrorAction SilentlyContinue | Measure-Object PrivateMemorySize64 -Sum).Sum / 1MB), 1)
    "$ts,$cpu,$ws,$priv" | Out-File -Append $csvPath
    Start-Sleep -Seconds 2
}
```

### Linux / macOS

```bash
# Sample every 2 seconds, append to CSV
echo "timestamp,cpu_pct,rss_kb" > results/host-metrics.csv
while true; do
  ts=$(date +%Y-%m-%dT%H:%M:%S)
  # CPU: aggregate across all php-fpm / artisan workers
  cpu=$(ps -eo %cpu,comm | awk '/php/{s+=$1} END{print s+0}')
  # RSS: sum of all php processes in KB
  rss=$(ps -eo rss,comm | awk '/php/{s+=$1} END{print s+0}')
  echo "$ts,$cpu,$rss" >> results/host-metrics.csv
  sleep 2
done
```

### Docker (ClamAV + Ollama)

If running ClamAV and Ollama in Docker:

```bash
# In a third terminal:
docker stats --no-stream --format "{{.Name}},{{.CPUPerc}},{{.MemUsage}}" \
  > results/docker-metrics.csv
```

**Stop the sampler** after k6 finishes.  The resulting CSV gives you
per-second CPU % and memory usage for the report tables.

---

## Output files

Each scenario writes results to `results/<scenario>.json`:

```
results/
  L1_dashboard_browsing.json
  L2_concurrent_upload.json
  L3_queue_saturation.json
  L4_mixed_workload.json
  host-metrics-*.csv          (you generate this)
```

### JSON schema (all scenarios)

```json
{
  "scenario": "L1_dashboard_browsing",
  "timestamp": "2025-...",
  "metrics": {
    "http_reqs": 150,
    "http_req_failed": 0.02,
    "http_req_duration_avg": 245.3,
    "http_req_duration_p95": 890.1,
    "http_req_duration_max": 2340.5,
    "errors_rate": 0.02,
    "iterations": 50,
    "vus_max": 10
  }
}
```

L2 adds `upload_count` and `upload_errors`.  
L3 adds `queue_depth_last`, `queue_depth_max`, `poll_count`, and the
`interpretation` block described above.

---

## Customisation

| Variable | Default | Purpose |
|----------|---------|---------|
| `BASE_URL` | `http://localhost:8000` | Application base URL |
| `PROJECT_ID` | `1` | Target project for uploads |
| `USER_ID` | `1` | User ID for auto-login (1 = admin) |
| `FIXTURE_PATH` | `./fixtures/test-evidence.txt` | File to upload |

Override via `--env`:

```bash
k6 run --env BASE_URL=http://compliance-hub.test:8000 --env USER_ID=2 L2_concurrent_upload.js
```

---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| 403 on `/auto-login` | `LOAD_TEST_BYPASS_ENABLED` not set | Add `LOAD_TEST_BYPASS_ENABLED=true` to `.env` and restart the server |
| 419 on POST endpoints | CSRF token missing or stale | Ensure the script GETs `/login` first; check k6 version ≥ 0.50 |
| 500 on upload | File validation failure | Verify `fixtures/test-evidence.txt` exists and is ≤ 20 MB |
| Queue never drains | Ollama not running or model not loaded | Run `ollama list` to check; start with `ollama serve` |
| `http_req_duration` very high | Inference bottleneck (expected for L3) | This is the point — L3 measures the queue, not HTTP speed |
