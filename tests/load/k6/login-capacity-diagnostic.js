import http from 'k6/http';
import { check } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const VUS = Number(__ENV.VUS || 20);
const MAX_ACCOUNT = 709;
const DURATION = __ENV.DURATION || '60s';

if (!Number.isInteger(VUS) || VUS < 1 || VUS > MAX_ACCOUNT) {
  throw new Error(`VUS must be an integer between 1 and ${MAX_ACCOUNT}`);
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

export const options = {
  scenarios: {
    capacity_test: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
      gracefulStop: '5s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.05'],
    login_success_rate: ['rate>0.95'],
  },
  summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
};

function extractCsrf(html) {
  const match = html.match(/<input[^>]+type=["']hidden["'][^>]+name=["']([^"']+)["'][^>]+value=["']([^"']*)["'][^>]*>/i);
  return match ? { name: match[1], value: match[2] } : null;
}

function accountForVU() {
  return String(__VU).padStart(3, '0');
}

export default function () {
  const username = accountForVU();
  const password = username;
  const startedAt = Date.now();

  loginAttempts.add(1);
  const loginPage = http.get(`${BASE_URL}/login`, {
    redirects: 0,
    tags: { request: 'login_page' },
  });
  loginPageDuration.add(loginPage.timings.duration);

  const csrf = extractCsrf(loginPage.body || '');
  const pageOk = check(loginPage, {
    'GET /login status 200': (r) => r.status === 200,
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

  const authResponse = http.post(`${BASE_URL}/auth`, {
    username,
    password,
    [csrf.name]: csrf.value,
  }, {
    redirects: 0,
    tags: { request: 'auth' },
  });

  authDuration.add(authResponse.timings.duration);

  const location = authResponse.headers.Location || authResponse.headers.location || '';
  const success = check(authResponse, {
    'POST /auth redirects': (r) => r.status >= 300 && r.status < 400,
    'POST /auth redirects to profile': () => location.includes('/siswa/users/profil'),
  });

  loginDuration.add(Date.now() - startedAt);
  loginSuccessRate.add(success);

  if (success) {
    authSuccess.add(1);
  } else {
    authFailure.add(1);
  }
}

export function handleSummary(data) {
  const m = data.metrics || {};
  const count = (name) => m[name]?.values?.count || 0;
  const rate = m.login_success_rate?.values?.rate || 0;

  const report = {
    test: {
      name: 'E-UJIAN login capacity diagnostic',
      base_url: BASE_URL,
      vus: VUS,
      duration: DURATION,
      generated_at: new Date().toISOString(),
    },
    result: {
      login_attempts: count('login_attempts'),
      csrf_success: count('csrf_success'),
      csrf_failure: count('csrf_failure'),
      auth_attempts: count('auth_attempts'),
      auth_success: count('auth_success'),
      auth_failure: count('auth_failure'),
      login_success_rate: rate,
      http_requests: m.http_reqs?.values?.count || 0,
      http_failed: m.http_req_failed?.values?.rate || 0,
    },
    metrics: m,
  };

  return {
    stdout: `\nE-UJIAN LOGIN CAPACITY DIAGNOSTIC\n================================\nBASE_URL       : ${BASE_URL}\nVUS            : ${VUS}\nDURATION       : ${DURATION}\nLOGIN ATTEMPTS : ${report.result.login_attempts}\nAUTH SUCCESS   : ${report.result.auth_success}\nAUTH FAILURE   : ${report.result.auth_failure}\nSUCCESS RATE   : ${(rate * 100).toFixed(2)}%\nHTTP FAILED    : ${(report.result.http_failed * 100).toFixed(2)}%\n`,
    [__ENV.K6_SUMMARY_FILE || 'summary.json']: JSON.stringify(report, null, 2),
  };
}
