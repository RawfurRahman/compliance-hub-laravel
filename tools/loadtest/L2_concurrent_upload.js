/**
 * L2 — Concurrent evidence upload.
 *
 * Simulates multiple users uploading evidence files simultaneously.
 * Each VU uploads a small text file to POST /evidence/{project}/upload
 * as multipart/form-data.  The upload triggers ClamAV scan + queued
 * analysis, so this also exercises the queue subsystem.
 *
 * Run:
 *   k6 run --env BASE_URL=http://localhost:8000 L2_concurrent_upload.js
 */

import http from "k6/http";
import { check, sleep } from "k6";
import { Rate, Trend, Counter } from "k6/metrics";
// open() is a k6 global — no import needed

// ── Metrics ───────────────────────────────────────────────────────
const errors = new Rate("errors");
const reqDuration = new Trend("req_duration", true);
const uploadCount = new Counter("upload_count");
const uploadErrors = new Counter("upload_errors");

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
    { duration: "10s", target: 1 },   // ramp up
    { duration: "30s", target: 3 },   // steady low
    { duration: "20s", target: 5 },   // steady medium
    { duration: "10s", target: 0 },   // ramp down
  ],
  thresholds: {
    http_req_duration: ["p(95)<5000"],
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

// ── Upload helper ─────────────────────────────────────────────────
function uploadEvidence(csrf, fileContent, fileName) {
  const payload = {
    file: http.file(fileContent, fileName, "text/plain"),
    requirement_id: "1",
    description: `Load test upload ${Date.now()}`,
  };

  const res = http.post(`${BASE_URL}/evidence/${PROJECT_ID}/upload`, payload, {
    headers: {
      "X-CSRF-TOKEN": csrf,
    },
    tags: { name: "evidence_upload" },
  });

  errors.add(res.status >= 400);
  reqDuration.add(res.timings.duration);
  uploadCount.add(1);

  if (res.status >= 400) {
    uploadErrors.add(1);
  }

  return res;
}

// ── Main ──────────────────────────────────────────────────────────
export function setup() {
  const csrf = authenticate();
  return { csrf };
}

export default function (data) {
  const csrf = data.csrf;

  const fileName = `evidence-vu${__VU}-iter${__ITER}.txt`;
  const res = uploadEvidence(csrf, fixtureContent, fileName);

  check(res, {
    "upload accepted": (r) => r.status === 200 || r.status === 302,
    "upload returns success": (r) => {
      try {
        const body = JSON.parse(r.body);
        return body.status === "success";
      } catch (_) {
        return r.status === 302; // redirect = success for non-JSON
      }
    },
  });

  // Wait between uploads to simulate realistic pacing
  sleep(Math.random() * 3 + 2);
}

export function handleSummary(data) {
  const results = {
    scenario: "L2_concurrent_upload",
    timestamp: new Date().toISOString(),
    metrics: {
      http_reqs: data.metrics.http_reqs?.values?.count || 0,
      http_req_failed: data.metrics.http_req_failed?.values?.rate || 0,
      http_req_duration_avg: data.metrics.http_req_duration?.values?.avg || 0,
      http_req_duration_p95: data.metrics.http_req_duration?.values?.["p(95)"] || 0,
      http_req_duration_max: data.metrics.http_req_duration?.values?.max || 0,
      errors_rate: data.metrics.errors?.values?.rate || 0,
      upload_count: data.metrics.upload_count?.values?.count || 0,
      upload_errors: data.metrics.upload_errors?.values?.count || 0,
      iterations: data.metrics.iterations?.values?.count || 0,
      vus_max: data.metrics.vus_max?.values?.value || 0,
    },
  };

  return {
    "results/L2_concurrent_upload.json": JSON.stringify(results, null, 2),
    stdout: JSON.stringify(results, null, 2) + "\n",
  };
}
