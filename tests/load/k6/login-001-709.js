import http from 'k6/http';
import { check } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

/**
 * Concurrent login load test for E-UJIAN.
 *
 * Accounts:
 *   username = 001 .. 709
 *   password = same value as username
 *
 * Example:
 *   k6 run tests/load/k6/login-001-709.js
 *   k6 run -e BASE_URL=https://e-ujian.example.com tests/load/k6/login-001-709.js
 *
 * The application uses a global CodeIgniter CSRF filter, so each VU first
 * loads /login, extracts the hidden CSRF field, then POSTs /auth using the
 * same VU cookie jar.
 */

const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const LOGIN_PATH = '/login';
const AUTH_PATH = '/auth';
const SUCCESS_PATH = '/siswa/users/profil';

const loginAttempts = new Counter('login_attempts');
const loginSuccess = new Counter('login_success');
const loginFailure = new Counter('login_failure');
const loginSuccessRate = new Rate('login_success_rate');
const loginDuration = new Trend('login_duration', true);

export const options = {
  scenarios: {
    concurrent_logins: {
      executor: 'shared-iterations',
      vus: 709,
      iterations: 709,
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
  // CodeIgniter csrf_field() normally renders a hidden input such as:
  // <input type="hidden" name="csrf_test_name" value="...">
  const match = html.match(/<input[^>]+type=["']hidden["'][^>]+name=["']([^"']+)["'][^>]+value=["']([^"']*)["'][^>]*>/i);
  if (!match) {
    return null;
  }

  return {
    name: match[1],
    value: match[2],
  };
}

function accountForVU() {
  // With shared-iterations + 709 VUs, each VU gets one deterministic account.
  // __VU is 1-based.
  const number = __VU;
  if (number < 1 || number > 709) {
    throw new Error(`Unexpected VU number: ${number}`);
  }

  return String(number).padStart(3, '0');
}

export default function () {
  const username = accountForVU();
  const password = username;
  const startedAt = Date.now();

  loginAttempts.add(1);

  // 1. Create session + obtain current CSRF token.
  const loginPage = http.get(`${BASE_URL}${LOGIN_PATH}`, {
    redirects: 0,
    tags: {
      request: 'login_page',
      username,
    },
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

  // 2. Submit credentials using the same VU's cookie jar.
  const payload = {
    username,
    password,
    [csrf.name]: csrf.value,
  };

  const authResponse = http.post(`${BASE_URL}${AUTH_PATH}`, payload, {
    redirects: 0,
    tags: {
      request: 'auth',
      username,
    },
  });

  // CodeIgniter returns a redirect after successful authentication.
  // For a student, Auth::auth() redirects to /siswa/users/profil.
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

    console.error(
      `[LOGIN FAILED] username=${username} status=${authResponse.status} location=${location || '<none>'}`
    );
  }
}

export function handleSummary(data) {
  const metrics = data.metrics || {};
  const attempts = metrics.login_attempts?.values?.count || 0;
  const success = metrics.login_success?.values?.count || 0;
  const failure = metrics.login_failure?.values?.count || 0;
  const successRate = metrics.login_success_rate?.values?.rate || 0;

  const summary = `
E-UJIAN K6 LOGIN TEST
=====================
BASE_URL       : ${BASE_URL}
ACCOUNTS       : 001-709
TOTAL ATTEMPTS : ${attempts}
SUCCESS        : ${success}
FAILURE        : ${failure}
SUCCESS RATE   : ${(successRate * 100).toFixed(2)}%
`;

  return {
    stdout: summary,
  };
}
