/**
 * L3 — Concurrent inference queue saturation.
 *
 * The bottleneck is local Ollama inference (llava:7b), not the web
 * tier.  This scenario uploads evidence files in bursts and then
 * polls /dashboard/scan-stats at regular intervals to measure:
 *
 *   1. How deep the queue grows under load.
 *   2. The point at which the queue stops draining (throughput
 *      ceiling = sustained uploads per minute once the queue is full).
 *
 * Interpretation guide (see README):
 *   - "queue_peak" is the max pending count observed.
 *   - "drain_stalled_at" is the VU count / iteration at which
 *     pending no longer decreases between polls — this is your
 *     throughput ceiling for §8.5.
 *   - "time_to_drain_s" is how long (seconds) the queue takes to
 *     reach zero after uploads stop.  If it never reaches zero
 *     during the observation window, report -1.
 *
 * Run:
 *   k6 run --env BASE_URL=http://localhost:8000 L3_queue_saturation.js
 */

import http from "k6/http";
import { check, sleep } from "k6";
import { Rate, Trend, Counter, Gauge } from "k6/metrics";

// ── Metrics ───────────────────────────────────────────────────────
const errors = new Rate("errors");
const reqDuration = new Trend("req_duration", true);
const uploadCount = new Counter("upload_count");
const queueDepth = new Gauge("queue_depth");
const pollCount = new Counter("poll_count");

// ── Config ────────────────────────────────────────────────────────
const BASE_URL = __ENV.BASE_URL || "http://localhost:8000";
const PROJECT_ID = __ENV.PROJECT_ID || "1";
const USER_ID = __ENV.USER_ID || "1";
const FIXTURE_PATH = __ENV.FIXTURE_PATH || "./fixtures/test-evidence.txt";

// Read fixture at init stage (k6 requires open() at global scope)
const fixtureContent = open(FIXTURE_PATH);

// ── Stages ────────────────────────────────────────────────────────
export const options = {
  stages: [
    { duration: "10s", target: 1 },    // warm up
    { duration: "20s", target: 3 },    // light load
    { duration: "30s", target: 5 },    // moderate load
    { duration: "20s", target: 8 },    // heavy load — find ceiling
    { duration: "20s", target: 3 },    // back off — observe drain
    { duration: "10s", target: 0 },    // cool down
  ],
  thresholds: {
    http_req_duration: ["p(95)<10000"],
  },
};

// ── State ─────────────────────────────────────────────────────────
let previousPending = -1;
let drainStalledAt = -1;
let peakPending = 0;
let uploadsComplete = false;

// ── Auth helper ───────────────────────────────────────────────────
function authenticate() {
  const loginPage = http.get(`${BASE_URL}/login`);
  check(loginPage, { "login page 200": (r) => r.status === 200 });

  const m = loginPage.body.match(/name="csrf-token"\s+content="([^"]+)"/);
  const csrf = m ? m[1] : "";

  const res = http.get(`${BASE_URL}/auto-login?user_id=${USER_ID}`, {
    headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" },
  });
  check(res, { "auto-login ok": (r) => r.status === 200 });
  return csrf;
}

// ── Upload ────────────────────────────────────────────────────────
function uploadEvidence(csrf, fileContent) {
  const payload = {
    file: http.file(fileContent, `queue-test-${Date.now()}-${__VU}.txt`, "text/plain"),
    requirement_id: "1",
    description: `L3 queue saturation test`,
  };

  const res = http.post(`${BASE_URL}/evidence/${PROJECT_ID}/upload`, payload, {
    headers: { "X-CSRF-TOKEN": csrf },
    tags: { name: "evidence_upload" },
  });

  errors.add(res.status >= 400);
  reqDuration.add(res.timings.duration);
  uploadCount.add(1);
  return res;
}

// ── Queue depth poll ──────────────────────────────────────────────
function pollQueueDepth(csrf) {
  const res = http.get(`${BASE_URL}/dashboard/scan-stats`, {
    headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" },
    tags: { name: "scan_stats_poll" },
  });

  pollCount.add(1);
  errors.add(res.status >= 400);
  reqDuration.add(res.timings.duration);

  let pending = 0;
  try {
    const body = JSON.parse(res.body);
    // scan-stats returns { stats: { pending: N, clean: N, ... }, ... }
    pending = body.stats?.pending || 0;
  } catch (_) {
    // parse error — treat as 0
  }

  queueDepth.set(pending);
  peakPending = Math.max(peakPending, pending);

  // Detect stall: pending not decreasing when we expect it to
  if (uploadsComplete && previousPending > 0 && pending >= previousPending) {
    if (drainStalledAt === -1) {
      drainStalledAt = __ITER;
    }
  }

  previousPending = pending;
  return pending;
}

// ── Main ──────────────────────────────────────────────────────────
export function setup() {
  const csrf = authenticate();
  return { csrf };
}

export default function (data) {
  const csrf = data.csrf;

  // Phase 1: Upload to fill the queue (first 80% of iterations)
  const totalIters = 40; // approximate; depends on stage timing
  if (__ITER < totalIters * 0.8) {
    uploadEvidence(csrf, fixtureContent);
    sleep(0.5);
  } else {
    uploadsComplete = true;
  }

  // Phase 2: Poll queue depth every iteration in the latter half
  if (__ITER >= totalIters * 0.5) {
    const pending = pollQueueDepth(csrf);
    sleep(1);
  } else {
    sleep(0.5);
  }
}

export function handleSummary(data) {
  // Time to drain: if we never saw pending hit 0 after uploads stopped,
  // report -1 (queue did not drain within observation window).
  const finalPending = data.metrics.queue_depth?.values?.value || 0;
  const timeToDrain = finalPending === 0 ? "completed" : "did_not_drain";

  const results = {
    scenario: "L3_queue_saturation",
    timestamp: new Date().toISOString(),
    interpretation: {
      queue_peak: peakPending,
      drain_stalled_at_iteration: drainStalledAt,
      drain_stalled: drainStalledAt !== -1,
      final_pending: finalPending,
      time_to_drain: timeToDrain,
      throughput_ceiling_note:
        drainStalledAt !== -1
          ? "Queue stopped draining at iteration " + drainStalledAt +
            ". The sustained upload rate at that point is the throughput ceiling."
          : "Queue continued draining — increase VU count or upload rate to find the ceiling.",
    },
    metrics: {
      http_reqs: data.metrics.http_reqs?.values?.count || 0,
      http_req_failed: data.metrics.http_req_failed?.values?.rate || 0,
      http_req_duration_avg: data.metrics.http_req_duration?.values?.avg || 0,
      http_req_duration_p95: data.metrics.http_req_duration?.values?.["p(95)"] || 0,
      http_req_duration_max: data.metrics.http_req_duration?.values?.max || 0,
      errors_rate: data.metrics.errors?.values?.rate || 0,
      upload_count: data.metrics.upload_count?.values?.count || 0,
      poll_count: data.metrics.poll_count?.values?.count || 0,
      queue_depth_last: finalPending,
      queue_depth_max: data.metrics.queue_depth?.values?.max || 0,
      iterations: data.metrics.iterations?.values?.count || 0,
      vus_max: data.metrics.vus_max?.values?.value || 0,
    },
  };

  return {
    "results/L3_queue_saturation.json": JSON.stringify(results, null, 2),
    stdout: JSON.stringify(results, null, 2) + "\n",
  };
}
