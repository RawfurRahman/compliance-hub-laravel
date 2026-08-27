/**
 * shared.js — Common helpers for all k6 load-test scenarios.
 *
 * Authentication strategy:
 *   The application uses email OTP as a second factor.  Rather than
 *   reverse-engineering the OTP flow, we gate a dedicated /auto-login
 *   route behind the env var LOAD_TEST_BYPASS_ENABLED (off by default).
 *   When enabled it calls Auth::loginUsingId() and returns JSON, seeding
 *   the session cookie that k6 carries into every subsequent request.
 *
 * CSRF handling:
 *   Web routes require a CSRF token.  k6 GETs /login first to obtain
 *   both the session cookie and the token from <meta name="csrf-token">,
 *   then includes the token in X-CSRF-TOKEN on every mutating request.
 */

import http from "k6/http";
import { check, group } from "k6";
import { Rate, Trend } from "k6/metrics";
import { htmlSelectHandler } from "https://jslib.k6.io/k6-http/0.7.0/response.js";

// ── Custom metrics exported by every scenario ─────────────────────
export const errorRate = new Rate("errors");
export const reqDuration = new Trend("req_duration", true);

// ── Configuration ─────────────────────────────────────────────────
export const BASE_URL = __ENV.BASE_URL || "http://localhost:8000";
export const PROJECT_ID = __ENV.PROJECT_ID || "1";
export const USER_ID = __ENV.USER_ID || "1";

// ── Authentication helper ─────────────────────────────────────────
// Returns an object { csrfToken, cookies } that the caller spreads
// into request params.
export function authenticate() {
  // Step 1 — GET /login to seed session cookie and grab CSRF token.
  const loginPage = http.get(`${BASE_URL}/login`, {
    tags: { name: "auth_login_page" },
  });

  check(loginPage, {
    "login page 200": (r) => r.status === 200,
  });

  // Extract CSRF token from <meta name="csrf-token" content="...">
  const csrfMatch = loginPage.body.match(
    /meta\s+name="csrf-token"\s+content="([^"]+)"/
  );
  const csrfToken = csrfMatch ? csrfMatch[1] : "";

  // Step 2 — Hit the environment-gated auto-login route.
  const loginRes = http.get(
    `${BASE_URL}/auto-login?user_id=${USER_ID}`,
    {
      headers: {
        "X-CSRF-TOKEN": csrfToken,
        Accept: "application/json",
      },
      tags: { name: "auth_auto_login" },
    }
  );

  check(loginRes, {
    "auto-login 200": (r) => r.status === 200,
    "auto-login ok": (r) => {
      try {
        return JSON.parse(r.body).status === "ok";
      } catch (_) {
        return false;
      }
    },
  });

  return { csrfToken };
}

// ── HTTP helpers that attach metrics + CSRF ───────────────────────
export function get(path, params = {}) {
  const res = http.get(`${BASE_URL}${path}`, {
    tags: { name: path },
    ...params,
  });
  errorRate.add(res.status >= 400);
  reqDuration.add(res.timings.duration);
  return res;
}

export function post(path, body, params = {}) {
  const res = http.post(`${BASE_URL}${path}`, body, {
    tags: { name: path },
    ...params,
  });
  errorRate.add(res.status >= 400);
  reqDuration.add(res.timings.duration);
  return res;
}

export function postMultipart(path, file, fileName, fields, csrfToken, params = {}) {
  const payload = {
    file: http.file(file, fileName, "text/plain"),
    ...fields,
  };
  const res = http.post(`${BASE_URL}${path}`, payload, {
    headers: {
      "X-CSRF-TOKEN": csrfToken,
    },
    tags: { name: path },
    ...params,
  });
  errorRate.add(res.status >= 400);
  reqDuration.add(res.timings.duration);
  return res;
}

// ── Result writer ─────────────────────────────────────────────────
export function writeResults(scenarioName, metrics) {
  const fs = require("fs");
  const path = `results/${scenarioName}.json`;
  fs.writeFileSync(path, JSON.stringify(metrics, null, 2));
}
