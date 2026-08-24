import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const LOGIN_PATH = '/login';
const AUTH_PATH = '/auth';
const SUCCESS_PATH = '/siswa/users/profil';

const TOTAL_USERS = parsePositiveInt(__ENV.TOTAL_USERS, 709);
const BATCH_SIZE = parsePositiveInt(__ENV.BATCH_SIZE, 50);
const BATCH_INTERVAL_SECONDS = parsePositiveInt(__ENV.BATCH_INTERVAL_SECONDS, 5);

if (TOTAL_USERS > 709) {
  throw new Error(`TOTAL_USERS cannot exceed 709. Received: ${TOTAL_USERS}`);
}
if (BATCH_SIZE > TOTAL_USERS) {
  throw new Error(`BATCH_SIZE cannot exceed TOTAL_USERS. Received: ${BATCH_SIZE}`);
}

function parsePositiveInt(value, fallback) {
  if (value === undefined || value === '') return fallback;
  const parsed = Number(value);
  if (!Number.isInteger(parsed) || parsed < 1) {
    throw new Error(`Expected a positive integer, received: ${value}`);
  }
  return parsed;
}

const loginAttempts = new Counter('login_attempts');
const csrfSuccess = new Counter('csrf_success');
const csrfFailure = new Counter('csrf_failure');
const authAttempts = new Counter('auth_attempts');
const authSuccess = new Counter('auth_success');
const authFailure = new Counter('auth_failure');
const loginSuccessRate = new Rate('login_success_rate');
const loginDuration = new Trend('login_duration', true);
const loginPageDuration = new Trend('login_page_duration', true);
const authDuration = new Trend('auth_duration', true);
const waitingDuration = new Trend('batch_waiting_duration', true);

export const options = {
  scenarios: {
    batch_login: {
      executor: 'shared-iterations',
      vus: TOTAL_USERS,
      iterations: TOTAL_USERS,
      maxDuration: `${Math.max(120, Math.ceil((TOTAL_USERS / BATCH_SIZE) * BATCH_INTERVAL_SECONDS) + 120)}s`,
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.05'],
    login_success_rate: ['rate>0.95'],
    login_duration: ['p(95)<3000'],
  },
  summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
};

function extractCsrf(html) {
  const match = html.match(/<input[^>]+type=["']hidden["'][^>]+name=["']([^"']+)["'][^>]+value=["']([^"']*)["'][^>]*>/i);
  if (!match) return null;
  return { name: match[1], value: match[2] };
}

function accountForVU() {
  const number = __VU;
  if (number < 1 || number > TOTAL_USERS || number > 709) {
    throw new Error(`Unexpected VU number: ${number}`);
  }
  return String(number).padStart(3, '0');
}

function waitForBatch() {
  const batchNumber = Math.floor((__VU - 1) / BATCH_SIZE);
  const waitSeconds = batchNumber * BATCH_INTERVAL_SECONDS;
  if (waitSeconds > 0) {
    sleep(waitSeconds);
  }
  waitingDuration.add(waitSeconds * 1000);
  return batchNumber + 1;
}

export default function () {
  const username = accountForVU();
  const batchNumber = waitForBatch();
  const startedAt = Date.now();
  loginAttempts.add(1);

  const loginPage = http.get(`${BASE_URL}${LOGIN_PATH}`, {
    redirects: 0,
    tags: { request: 'login_page', username, batch: String(batchNumber) },
  });
  loginPageDuration.add(loginPage.timings.duration);

  const csrf = extractCsrf(loginPage.body || '');
  const pageOk = check(loginPage, {
    'login page status is 200': (r) => r.status === 200,
    'CSRF token exists': () => csrf !== null && csrf.value.length > 0,
  });

  if (!pageOk || !csrf) {
    csrfFailure.add(1);
    loginSuccessRate.add(false);
    loginDuration.add(Date.now() - startedAt);
    return;
  }

  csrfSuccess.add(1);
  authAttempts.add(1);

  const payload = { username, password: username, [csrf.name]: csrf.value };
  const authResponse = http.post(`${BASE_URL}${AUTH_PATH}`, payload, {
    redirects: 0,
    tags: { request: 'auth', username, batch: String(batchNumber) },
  });
  authDuration.add(authResponse.timings.duration);

  const location = authResponse.headers.Location || authResponse.headers.location || '';
  const success = check(authResponse, {
    'auth returns redirect': (r) => r.status >= 300 && r.status < 400,
    'auth redirects to student profile': () => location.includes(SUCCESS_PATH),
  });

  loginDuration.add(Date.now() - startedAt);
  loginSuccessRate.add(success);

  if (success) {
    authSuccess.add(1);
  } else {
    authFailure.add(1);
    console.error(`[AUTH FAILED] batch=${batchNumber} username=${username} status=${authResponse.status} location=${location || '<none>'}`);
  }
}

export function handleSummary(data) {
  const metrics = data.metrics || {};
  const count = (name) => metrics[name]?.values?.count || 0;
  const rate = metrics.login_success_rate?.values?.rate || 0;

  const attempts = count('login_attempts');
  const csrfOk = count('csrf_success');
  const csrfBad = count('csrf_failure');
  const authTry = count('auth_attempts');
  const success = count('auth_success');
  const failure = count('auth_failure');

  const report = {
    test: {
      name: 'E-UJIAN K6 batch login',
      base_url: BASE_URL,
      total_users: TOTAL_USERS,
      batch_size: BATCH_SIZE,
      batch_interval_seconds: BATCH_INTERVAL_SECONDS,
      batch_count: Math.ceil(TOTAL_USERS / BATCH_SIZE),
      account_range: `001-${String(TOTAL_USERS).padStart(3, '0')}`,
      executor: 'shared-iterations',
      generated_at: new Date().toISOString(),
    },
    requests: {
      get_login: attempts,
      post_auth: authTry,
      total_http_requests: attempts + authTry,
    },
    result: {
      login_attempts: attempts,
      csrf_success: csrfOk,
      csrf_failure: csrfBad,
      auth_attempts: authTry,
      auth_success: success,
      auth_failure: failure,
      login_success_rate: rate,
    },
    metrics: data.metrics,
  };

  return {
    stdout: `\nE-UJIAN K6 BATCH LOGIN TEST\n===========================\nBASE_URL          : ${BASE_URL}\nTOTAL USERS       : ${TOTAL_USERS}\nBATCH SIZE        : ${BATCH_SIZE}\nBATCH INTERVAL    : ${BATCH_INTERVAL_SECONDS}s\nBATCH COUNT       : ${Math.ceil(TOTAL_USERS / BATCH_SIZE)}\nACCOUNT RANGE     : 001-${String(TOTAL_USERS).padStart(3, '0')}\nEXECUTOR          : shared-iterations\nGET /login        : ${attempts}\nCSRF SUCCESS      : ${csrfOk}\nCSRF FAILURE      : ${csrfBad}\nPOST /auth        : ${authTry}\nAUTH SUCCESS      : ${success}\nAUTH FAILURE      : ${failure}\nTOTAL HTTP REQ    : ${attempts + authTry}\nSUCCESS RATE      : ${(rate * 100).toFixed(2)}%\n`,
    [__ENV.K6_SUMMARY_FILE || 'summary.json']: JSON.stringify(report, null, 2),
  };
}
