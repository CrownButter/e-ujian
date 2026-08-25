import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const TOTAL_USERS = parsePositiveInt(__ENV.TOTAL_USERS, 30);
const MAX_WAIT_SECONDS = parsePositiveInt(__ENV.MAX_WAIT_SECONDS, 60);
const POLL_INTERVAL_SECONDS = parsePositiveInt(__ENV.POLL_INTERVAL_SECONDS, 2);
const SUCCESS_PATH = '/siswa/users/profil';

function parsePositiveInt(value, fallback) {
  if (value === undefined || value === '') return fallback;
  const parsed = Number(value);
  if (!Number.isInteger(parsed) || parsed < 1) {
    throw new Error(`Expected positive integer, received: ${value}`);
  }
  return parsed;
}

if (TOTAL_USERS > 709) {
  throw new Error(`TOTAL_USERS cannot exceed 709. Received: ${TOTAL_USERS}`);
}

const loginAttempts = new Counter('login_attempts');
const loginPageSuccess = new Counter('login_page_success');
const waitingRoomAttempts = new Counter('waiting_room_attempts');
const waitingRoomSuccess = new Counter('waiting_room_success');
const waitingRoomErrors = new Counter('waiting_room_errors');
const queueReady = new Counter('queue_ready');
const queueExpired = new Counter('queue_expired');
const queueTimeout = new Counter('queue_timeout');
const authAttempts = new Counter('auth_attempts');
const authSuccess = new Counter('auth_success');
const authFailure = new Counter('auth_failure');
const loginSuccessRate = new Rate('login_success_rate');
const httpFailedRate = new Rate('http_failed_rate');
const loginDuration = new Trend('login_duration', true);
const loginPageDuration = new Trend('login_page_duration', true);
const waitingRoomDuration = new Trend('waiting_room_duration', true);
const queueWaitDuration = new Trend('queue_wait_duration', true);
const authDuration = new Trend('auth_duration', true);

export const options = {
  scenarios: {
    waiting_room_login: {
      executor: 'per-vu-iterations',
      vus: TOTAL_USERS,
      iterations: 1,
      maxDuration: `${MAX_WAIT_SECONDS + 60}s`,
      gracefulStop: '10s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.05'],
    login_success_rate: ['rate>0.95'],
  },
  summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
};

function extractCsrf(html) {
  const patterns = [
    /<input[^>]+name=["']([^"']+)["'][^>]+value=["']([^"']*)["'][^>]*>/i,
    /<input[^>]+value=["']([^"']*)["'][^>]+name=["']([^"']+)["'][^>]*>/i,
  ];

  for (let i = 0; i < patterns.length; i++) {
    const match = html.match(patterns[i]);
    if (!match) continue;
    return i === 0
      ? { name: match[1], value: match[2] }
      : { name: match[2], value: match[1] };
  }
  return null;
}

function usernameForVU() {
  if (__VU < 1 || __VU > TOTAL_USERS) {
    throw new Error(`Unexpected VU=${__VU}, total=${TOTAL_USERS}`);
  }
  return String(__VU).padStart(3, '0');
}

function recordHttpFailure(response) {
  const failed = !response || response.status === 0 || response.status >= 500;
  httpFailedRate.add(failed);
}

function jsonBody(response) {
  try {
    return JSON.parse(response.body || '{}');
  } catch (_) {
    return null;
  }
}

export default function () {
  const username = usernameForVU();
  const loginStartedAt = Date.now();
  loginAttempts.add(1);

  const loginPage = http.get(`${BASE_URL}/login`, {
    redirects: 0,
    tags: { request: 'login_page', username },
  });
  loginPageDuration.add(loginPage.timings.duration);
  recordHttpFailure(loginPage);

  const csrf = extractCsrf(loginPage.body || '');
  const pageOk = check(loginPage, {
    'GET /login status 200': r => r.status === 200,
    'login CSRF token exists': () => csrf !== null,
  });

  if (!pageOk || !csrf || !csrf.value) {
    loginSuccessRate.add(false);
    return;
  }
  loginPageSuccess.add(1);

  const queueStart = Date.now();
  waitingRoomAttempts.add(1);
  let currentCsrf = csrf.value;
  const enterResponse = http.post(
    `${BASE_URL}/waiting-room/enter`,
    { [csrf.name]: csrf.value },
    { redirects: 0, tags: { request: 'waiting_room_enter', username } },
  );
  waitingRoomDuration.add(enterResponse.timings.duration);
  recordHttpFailure(enterResponse);

  const enterData = jsonBody(enterResponse);
  if (
    enterResponse.status !== 200 ||
    !enterData ||
    enterData.ok !== true ||
    typeof enterData.ticket !== 'string'
  ) {
    waitingRoomErrors.add(1);
    loginSuccessRate.add(false);
    return;
  }
  waitingRoomSuccess.add(1);
  if (enterData.csrf_token) currentCsrf = enterData.csrf_token;

  const ticket = enterData.ticket;
  let status = enterData.status;
  const deadline = Date.now() + MAX_WAIT_SECONDS * 1000;

  while (status !== 'ready' && Date.now() < deadline) {
    sleep(POLL_INTERVAL_SECONDS);

    const response = http.get(
      `${BASE_URL}/waiting-room/status?ticket=${encodeURIComponent(ticket)}`,
      { redirects: 0, tags: { request: 'waiting_room_status', username } },
    );
    recordHttpFailure(response);

    const data = jsonBody(response);
    if (response.status === 410 || (data && data.expired === true)) {
      queueExpired.add(1);
      loginSuccessRate.add(false);
      return;
    }

    if (response.status !== 200 || !data || data.ok !== true) {
      waitingRoomErrors.add(1);
      loginSuccessRate.add(false);
      return;
    }

    status = data.status;
    if (data.csrf_token) currentCsrf = data.csrf_token;
  }

  if (status !== 'ready') {
    queueTimeout.add(1);
    loginSuccessRate.add(false);
    return;
  }

  queueReady.add(1);
  queueWaitDuration.add(Date.now() - queueStart);

  authAttempts.add(1);
  const authResponse = http.post(
    `${BASE_URL}/auth`,
    {
      username,
      password: username,
      [csrf.name]: currentCsrf,
      waiting_room_ticket: ticket,
    },
    { redirects: 0, tags: { request: 'auth', username } },
  );
  authDuration.add(authResponse.timings.duration);
  recordHttpFailure(authResponse);

  const location = authResponse.headers.Location || authResponse.headers.location || '';
  const ok = check(authResponse, {
    'POST /auth returns redirect': r => r.status >= 300 && r.status < 400,
    'POST /auth redirects to student profile': () => location.includes(SUCCESS_PATH),
  });

  loginDuration.add(Date.now() - loginStartedAt);
  loginSuccessRate.add(ok);

  if (ok) authSuccess.add(1);
  else authFailure.add(1);
}

export function handleSummary(data) {
  const metrics = data.metrics || {};
  const count = name => metrics[name]?.values?.count || 0;
  const rate = name => metrics[name]?.values?.rate || 0;
  const pct = (name, key) => metrics[name]?.values?.[key] || 0;

  const report = {
    test: {
      name: 'E-UJIAN focused login bottleneck diagnostic',
      base_url: BASE_URL,
      total_users: TOTAL_USERS,
      max_wait_seconds: MAX_WAIT_SECONDS,
      poll_interval_seconds: POLL_INTERVAL_SECONDS,
      generated_at: new Date().toISOString(),
    },
    result: {
      login_attempts: count('login_attempts'),
      login_page_success: count('login_page_success'),
      waiting_room_attempts: count('waiting_room_attempts'),
      waiting_room_success: count('waiting_room_success'),
      waiting_room_errors: count('waiting_room_errors'),
      queue_ready: count('queue_ready'),
      queue_expired: count('queue_expired'),
      queue_timeout: count('queue_timeout'),
      auth_attempts: count('auth_attempts'),
      auth_success: count('auth_success'),
      auth_failure: count('auth_failure'),
      login_success_rate: rate('login_success_rate'),
      http_failed_rate: rate('http_failed_rate'),
    },
    latency: {
      login_page_p95_ms: pct('login_page_duration', 'p(95)'),
      waiting_room_p95_ms: pct('waiting_room_duration', 'p(95)'),
      queue_wait_p95_ms: pct('queue_wait_duration', 'p(95)'),
      auth_p95_ms: pct('auth_duration', 'p(95)'),
      login_p95_ms: pct('login_duration', 'p(95)'),
      login_p99_ms: pct('login_duration', 'p(99)'),
    },
    metrics,
  };

  return {
    stdout: `\nE-UJIAN FOCUSED LOGIN BOTTLENECK DIAGNOSTIC\n===========================================\nBASE_URL           : ${BASE_URL}\nTOTAL USERS        : ${TOTAL_USERS}\nLOGIN SUCCESS      : ${(rate('login_success_rate') * 100).toFixed(2)}%\nHTTP FAILED        : ${(rate('http_failed_rate') * 100).toFixed(2)}%\nQUEUE READY        : ${count('queue_ready')}\nQUEUE EXPIRED      : ${count('queue_expired')}\nQUEUE TIMEOUT      : ${count('queue_timeout')}\nAUTH SUCCESS       : ${count('auth_success')}\nAUTH FAILURE       : ${count('auth_failure')}\nLOGIN PAGE P95     : ${pct('login_page_duration', 'p(95)')} ms\nQUEUE WAIT P95     : ${pct('queue_wait_duration', 'p(95)')} ms\nAUTH P95           : ${pct('auth_duration', 'p(95)')} ms\nLOGIN P95          : ${pct('login_duration', 'p(95)')} ms\nLOGIN P99          : ${pct('login_duration', 'p(99)')} ms\n`,
    [__ENV.K6_SUMMARY_FILE || 'summary.json']: JSON.stringify(report, null, 2),
  };
}
