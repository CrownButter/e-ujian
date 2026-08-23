import http from 'k6/http';
import { check } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const LOGIN_PATH = '/login';
const AUTH_PATH = '/auth';
const SUCCESS_PATH = '/siswa/users/profil';

// One deterministic login per VU.
// VU 1 -> account 001, VU 2 -> account 002, ..., VU 709 -> account 709.
const VUS = parsePositiveInt(__ENV.VUS, 709);
const ITERATIONS = parsePositiveInt(__ENV.ITERATIONS, VUS);
const MAX_ACCOUNT = 709;

if (VUS > MAX_ACCOUNT) {
  throw new Error(`VUS cannot exceed ${MAX_ACCOUNT}. Received: ${VUS}`);
}

// The monitoring wrapper keeps ITERATIONS equal to VUS for backwards compatibility.
// The actual executor is per-vu-iterations with exactly one login per VU.
if (ITERATIONS !== VUS) {
  throw new Error(`For deterministic one-login-per-VU testing, ITERATIONS must equal VUS. Received VUS=${VUS}, ITERATIONS=${ITERATIONS}`);
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

export const options = {
  scenarios: {
    concurrent_logins: {
      executor: 'per-vu-iterations',
      vus: VUS,
      iterations: 1,
      maxDuration: '10m',
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
  if (number < 1 || number > VUS || number > MAX_ACCOUNT) {
    throw new Error(`Unexpected VU number: ${number}`);
  }
  return String(number).padStart(3, '0');
}

export default function () {
  const username = accountForVU();
  const password = username;
  const startedAt = Date.now();
  loginAttempts.add(1);

  const loginPage = http.get(`${BASE_URL}${LOGIN_PATH}`, {
    redirects: 0,
    tags: { request: 'login_page', username },
  });

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

  const payload = { username, password, [csrf.name]: csrf.value };

  const authResponse = http.post(`${BASE_URL}${AUTH_PATH}`, payload, {
    redirects: 0,
    tags: { request: 'auth', username },
  });

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
    console.error(`[AUTH FAILED] username=${username} status=${authResponse.status} location=${location || '<none>'}`);
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
      name: 'E-UJIAN concurrent login',
      base_url: BASE_URL,
      accounts: `001-${String(VUS).padStart(3, '0')}`,
      vus: VUS,
      iterations: VUS,
      iterations_per_vu: 1,
      executor: 'per-vu-iterations',
      generated_at: new Date().toISOString(),
    },
    requests: {
      expected_get_login: attempts,
      expected_post_auth: authTry,
      expected_total_http_requests: attempts + authTry,
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
    stdout: `\nE-UJIAN K6 LOGIN TEST\n=====================\nBASE_URL          : ${BASE_URL}\nACCOUNTS          : 001-${String(VUS).padStart(3, '0')}\nVUS               : ${VUS}\nITERATIONS        : ${VUS} (1 per VU)\nGET /login        : ${attempts}\nCSRF SUCCESS      : ${csrfOk}\nCSRF FAILURE      : ${csrfBad}\nPOST /auth        : ${authTry}\nAUTH SUCCESS      : ${success}\nAUTH FAILURE      : ${failure}\nEXPECTED HTTP REQ : ${attempts + authTry}\nSUCCESS RATE      : ${(rate * 100).toFixed(2)}%\n`,
    [__ENV.K6_SUMMARY_FILE || 'summary.json']: JSON.stringify(report, null, 2),
  };
}
