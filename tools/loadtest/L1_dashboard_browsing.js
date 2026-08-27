/**
 * L1 — Dashboard browsing (read-only, authenticated).
 *
 * Simulates authenticated users browsing the main dashboard and its
 * JSON API endpoints.  No mutations; purely read traffic.
 *
 * Run:
 *   k6 run --env BASE_URL=http://localhost:8000 L1_dashboard_browsing.js
 */

import http from "k6/http";
import { check, sleep } from "k6";
import { Rate, Trend, Counter } from "k6/metrics";

// ── Metrics ───────────────────────────────────────────────────────
const errors = new Rate("errors");
const reqDuration = new Trend("req_duration", true);
const reqCount = new Counter("req_count");

// ── Config ────────────────────────────────────────────────────────
const BASE_URL = __ENV.BASE_URL || "http://localhost:8000";
const PROJECT_ID = __ENV.PROJECT_ID || "1";
const USER_ID = __ENV.USER_ID || "1";

// ── Stages ────────────────────────────────────────────────────────
export const options = {
  stages: [
    { duration: "10s", target: 1 },   // ramp up
    { duration: "30s", target: 5 },   // steady low
    { duration: "20s", target: 10 },  // steady medium
    { duration: "10s", target: 0 },   // ramp down
  ],
  thresholds: {
    http_req_duration: ["p(95)<2000"],
    errors: ["rate<0.1"],
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
  check(res, {
    "auto-login ok": (r) => r.status === 200,
  });
  return csrf;
}

// ── Request helper ────────────────────────────────────────────────
function apiGet(path, csrf) {
  const res = http.get(`${BASE_URL}${path}`, {
    headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" },
    tags: { name: path },
  });
  errors.add(res.status >= 400);
  reqDuration.add(res.timings.duration);
  reqCount.add(1);
  return res;
}

// ── Endpoints to cycle through ────────────────────────────────────
const HTML_PAGES = [
  "/dashboard",
  `/evidence-hub/${PROJECT_ID}`,
  `/projects/${PROJECT_ID}`,
];

const JSON_ENDPOINTS = [
  "/api/v1/dashboard/kpis",
  "/api/v1/dashboard/heatmap",
  "/api/v1/dashboard/top-risks",
  "/api/v1/dashboard/control-effectiveness",
  "/api/v1/dashboard/compliance-scorecard",
  "/api/v1/dashboard/tests-summary",
  "/api/v1/dashboard/user",
  "/dashboard/scan-stats",
];

// ── Main ──────────────────────────────────────────────────────────
export function setup() {
  const csrf = authenticate();
  return { csrf };
}

export default function (data) {
  const csrf = data.csrf;

  // Browse one HTML page
  const page = HTML_PAGES[__VU % HTML_PAGES.length];
  const pageRes = http.get(`${BASE_URL}${page}`, {
    headers: { "X-CSRF-TOKEN": csrf },
    tags: { name: page },
  });
  errors.add(pageRes.status >= 400);
  reqDuration.add(pageRes.timings.duration);
  reqCount.add(1);
  check(pageRes, { [`${page} 200`]: (r) => r.status === 200 });

  sleep(1);

  // Hit 2-3 JSON endpoints
  const numJson = 2 + (__VU % 2);
  for (let i = 0; i < numJson; i++) {
    const ep = JSON_ENDPOINTS[(__ITER + i) % JSON_ENDPOINTS.length];
    const res = apiGet(ep, csrf);
    check(res, { [`${ep} 200`]: (r) => r.status === 200 });
    sleep(0.5);
  }

  sleep(Math.random() * 2 + 1);
}

export function handleSummary(data) {
  const results = {
    scenario: "L1_dashboard_browsing",
    timestamp: new Date().toISOString(),
    metrics: {
      http_reqs: data.metrics.http_reqs?.values?.count || 0,
      http_req_failed: data.metrics.http_req_failed?.values?.rate || 0,
      http_req_duration_avg: data.metrics.http_req_duration?.values?.avg || 0,
      http_req_duration_p95: data.metrics.http_req_duration?.values?.["p(95)"] || 0,
      http_req_duration_max: data.metrics.http_req_duration?.values?.max || 0,
      errors_rate: data.metrics.errors?.values?.rate || 0,
      iterations: data.metrics.iterations?.values?.count || 0,
      vus_max: data.metrics.vus_max?.values?.value || 0,
    },
  };

  return {
    "results/L1_dashboard_browsing.json": JSON.stringify(results, null, 2),
    stdout: JSON.stringify(results, null, 2) + "\n",
  };
}
