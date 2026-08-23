import http from 'k6/http';
import { check } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const LOGIN_PATH = '/login';
const AUTH_PATH = '/auth';
const SUCCESS_PATH = '/siswa/users/profil';

// VUS controls how many accounts are tested concurrently.
// Example: VUS=1 => account 001, VUS=10 => 001-010, VUS=709 => 001-709.
const VUS = parsePositiveInt(__ENV.VUS, 709);
const ITERATIONS = parsePositiveInt(__ENV.ITERATIONS, VUS);
const MAX_ACCOUNT = 709;

if (VUS > MAX_ACCOUNT) {
  throw new Error(`VUS cannot exceed ${MAX_ACCOUNT}. Received: ${VUS}`);
}

if (ITERATIONS < VUS) {
  throw new Error(`ITERATIONS must be >= VUS so every VU can receive a unique account. Received VUS=${VUS}, ITERATIONS=${ITERATIONS}`);
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
const loginSuccess = new Counter('login_success');
const loginFailure = new Counter('login_failure');
const loginSuccessRate = new Rate('login_success_rate');
const loginDuration = new Trend('login_duration', true);

export const options = {
  scenarios: {
    concurrent_logins: {
      executor: 'shared-iterations',
      vus: VUS,
      iterations: ITERATIONS,
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
  // __VU is 1-based. With VUS=N, VU N uses account N.
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
  const csrfFound = check(loginPage, {
    'login page status is 200': (r) => r.status === 200,
    'CSRF token exists': () => csrf !== null && csrf.value.length > 0,
  });

  if (!csrfFound || !csrf) {
    loginFailure.add(1);
    loginSuccessRate.add(false);
    loginDuration.add(Date.now() - startedAt);
    return;
  }

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
    loginSuccess.add(1);
  } else {
    loginFailure.add(1);
    console.error(`[LOGIN FAILED] username=${username} status=${authResponse.status} location=${location || '<none>'}`);
  }
}

export function handleSummary(data) {
  const metrics = data.metrics || {};
  const attempts = metrics.login_attempts?.values?.count || 0;
  const success = metrics.login_success?.values?.count || 0;
  const failure = metrics.login_failure?.values?.count || 0;
  const successRate = metrics.login_success_rate?.values?.rate || 0;

  const report = {
    test: {
      name: 'E-UJIAN concurrent login',
      base_url: BASE_URL,
      accounts: `001-${String(VUS).padStart(3, '0')}`,
      vus: VUS,
      iterations: ITERATIONS,
      generated_at: new Date().toISOString(),
    },
    result: {
      attempts,
      success,
      failure,
      success_rate: successRate,
    },
    metrics: data.metrics,
  };

  return {
    stdout: `\nE-UJIAN K6 LOGIN TEST\n=====================\nBASE_URL       : ${BASE_URL}\nACCOUNTS       : 001-${String(VUS).padStart(3, '0')}\nVUS            : ${VUS}\nITERATIONS     : ${ITERATIONS}\nTOTAL ATTEMPTS : ${attempts}\nSUCCESS        : ${success}\nFAILURE        : ${failure}\nSUCCESS RATE   : ${(successRate * 100).toFixed(2)}%\n`,
    [__ENV.K6_SUMMARY_FILE || 'summary.json']: JSON.stringify(report, null, 2),
  };
}
