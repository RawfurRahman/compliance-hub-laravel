/**
 * L4 — Mixed realistic workload.
 *
 * Combines dashboard browsing, evidence upload, and queue polling
 * in proportions that mirror real auditor usage:
 *   - 60% read (dashboard pages + JSON API)
 *   - 30% write (evidence upload)
 *   - 10% monitoring (scan-stats poll)
 *
 * Each VU randomly picks an action per iteration weighted by these
 * proportions.
 *
 * Run:
 *   k6 run --env BASE_URL=http://localhost:8000 L4_mixed_workload.js
 */

import http from "k6/http";
import { check, sleep } from "k6";
import { Rate, Trend, Counter } from "k6/metrics";

// ── Metrics ───────────────────────────────────────────────────────
const errors = new Rate("errors");
const reqDuration = new Trend("req_duration", true);
const actionCount = new Counter("action_count");

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
    { duration: "10s", target: 1 },   // warm up
    { duration: "30s", target: 5 },   // steady
    { duration: "30s", target: 10 },  // peak
    { duration: "20s", target: 5 },   // sustain
    { duration: "10s", target: 0 },   // ramp down
  ],
  thresholds: {
    http_req_duration: ["p(95)<3000"],
    errors: ["rate<0.15"],
  },
};

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

// ── Actions ───────────────────────────────────────────────────────
const DASHBOARD_PAGES = ["/dashboard", "/evidence-hub", "/projects"];
const JSON_ENDPOINTS = [
  "/api/v1/dashboard/kpis",
  "/api/v1/dashboard/heatmap",
  "/api/v1/dashboard/top-risks",
  "/api/v1/dashboard/control-effectiveness",
  "/api/v1/dashboard/compliance-scorecard",
  "/api/v1/dashboard/tests-summary",
  "/dashboard/scan-stats",
];

function browseDashboard(csrf) {
  // 50% chance HTML page, 50% JSON endpoint
  if (Math.random() < 0.5) {
    const page = DASHBOARD_PAGES[__VU % DASHBOARD_PAGES.length];
    const suffix = page === "/evidence-hub" ? `/${PROJECT_ID}` : page === "/projects" ? `/${PROJECT_ID}` : "";
    const res = http.get(`${BASE_URL}${page}${suffix}`, {
      headers: { "X-CSRF-TOKEN": csrf },
      tags: { name: page },
    });
    errors.add(res.status >= 400);
    reqDuration.add(res.timings.duration);
    actionCount.add(1);
    check(res, { [`${page} ok`]: (r) => r.status === 200 });
  } else {
    const ep = JSON_ENDPOINTS[__VU % JSON_ENDPOINTS.length];
    const res = http.get(`${BASE_URL}${ep}`, {
      headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" },
      tags: { name: ep },
    });
    errors.add(res.status >= 400);
    reqDuration.add(res.timings.duration);
    actionCount.add(1);
    check(res, { [`${ep} ok`]: (r) => r.status === 200 });
  }
}

function uploadEvidence(csrf, fileContent) {
  const payload = {
    file: http.file(fileContent, `mixed-vu${__VU}-${Date.now()}.txt`, "text/plain"),
    requirement_id: "1",
    description: `L4 mixed workload upload`,
  };
  const res = http.post(`${BASE_URL}/evidence/${PROJECT_ID}/upload`, payload, {
    headers: { "X-CSRF-TOKEN": csrf },
    tags: { name: "evidence_upload" },
  });
  errors.add(res.status >= 400);
  reqDuration.add(res.timings.duration);
  actionCount.add(1);
  check(res, { "upload ok": (r) => r.status === 200 || r.status === 302 });
}

function pollScanStats(csrf) {
  const res = http.get(`${BASE_URL}/dashboard/scan-stats`, {
    headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" },
    tags: { name: "scan_stats" },
  });
  errors.add(res.status >= 400);
  reqDuration.add(res.timings.duration);
  actionCount.add(1);
  check(res, { "scan-stats ok": (r) => r.status === 200 });
}

// ── Main ──────────────────────────────────────────────────────────
export function setup() {
  const csrf = authenticate();
  return { csrf };
}

export default function (data) {
  const csrf = data.csrf;

  // Weighted random action selection
  const roll = Math.random();

  if (roll < 0.6) {
    // 60% — read (dashboard browsing)
    browseDashboard(csrf);
    sleep(Math.random() * 2 + 1);
  } else if (roll < 0.9) {
    // 30% — write (evidence upload)
    uploadEvidence(csrf, fixtureContent);
    sleep(Math.random() * 3 + 2);
  } else {
    // 10% — monitoring (scan-stats poll)
    pollScanStats(csrf);
    sleep(1);
  }
}

export function handleSummary(data) {
  const results = {
    scenario: "L4_mixed_workload",
    timestamp: new Date().toISOString(),
    weights: { read: "60%", write: "30%", monitor: "10%" },
    metrics: {
      http_reqs: data.metrics.http_reqs?.values?.count || 0,
      http_req_failed: data.metrics.http_req_failed?.values?.rate || 0,
      http_req_duration_avg: data.metrics.http_req_duration?.values?.avg || 0,
      http_req_duration_p95: data.metrics.http_req_duration?.values?.["p(95)"] || 0,
      http_req_duration_max: data.metrics.http_req_duration?.values?.max || 0,
      errors_rate: data.metrics.errors?.values?.rate || 0,
      action_count: data.metrics.action_count?.values?.count || 0,
      iterations: data.metrics.iterations?.values?.count || 0,
      vus_max: data.metrics.vus_max?.values?.value || 0,
    },
  };

  return {
    "results/L4_mixed_workload.json": JSON.stringify(results, null, 2),
    stdout: JSON.stringify(results, null, 2) + "\n",
  };
}
