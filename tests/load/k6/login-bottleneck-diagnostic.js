import http from 'k6/http';
import { check } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const VUS = Number(__ENV.VUS || 10);
const DURATION = __ENV.DURATION || '60s';
const MAX_ACCOUNT = 709;

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
const loginPageDuration = new Trend('login_page_duration', true);
const authDuration = new Trend('auth_duration', true);
const endToEndDuration = new Trend('login_duration', true);

export const options = {
  scenarios: {
    bottleneck_test: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
      gracefulStop: '5s',
    },
  },
  thresholds: {},
  summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
};

function extractCsrf(html) {
  if (!html) return null;
  const patterns = [
    /<input[^>]+type=["']hidden["'][^>]+name=["']([^"']+)["'][^>]+value=["']([^"']*)["'][^>]*>/i,
    /<input[^>]+name=["']([^"']+)["'][^>]+value=["']([^"']*)["'][^>]+type=["']hidden["'][^>]*>/i,
  ];
  for (const pattern of patterns) {
    const match = html.match(pattern);
    if (match) return { name: match[1], value: match[2] };
  }
  return null;
}

function accountForVU() {
  return String(__VU).padStart(3, '0');
}

export default function () {
  const username = accountForVU();
  const password = username;
  const started = Date.now();

  loginAttempts.add(1);
  const page = http.get(`${BASE_URL}/login`, {
    redirects: 0,
    tags: { request: 'login_page' },
  });
  loginPageDuration.add(page.timings.duration);

  const csrf = extractCsrf(page.body || '');
  const pageOk = check(page, {
    'GET /login status 200': (r) => r.status === 200,
    'CSRF exists': () => csrf !== null && csrf.value.length > 0,
  });

  if (!pageOk || !csrf) {
    csrfFailure.add(1);
    loginSuccessRate.add(false);
    endToEndDuration.add(Date.now() - started);
    return;
  }

  csrfSuccess.add(1);
  authAttempts.add(1);

  const auth = http.post(`${BASE_URL}/auth`, {
    username,
    password,
    [csrf.name]: csrf.value,
  }, {
    redirects: 0,
    tags: { request: 'auth' },
  });
  authDuration.add(auth.timings.duration);

  const location = auth.headers.Location || auth.headers.location || '';
  const success = check(auth, {
    'POST /auth returns redirect': (r) => r.status >= 300 && r.status < 400,
    'POST /auth redirects to expected page': () =>
      location.includes('/siswa/users/profil') || location.includes('/dashboard'),
  });

  endToEndDuration.add(Date.now() - started);
  loginSuccessRate.add(success);

  if (success) authSuccess.add(1);
  else authFailure.add(1);
}

export function handleSummary(data) {
  const metrics = data.metrics || {};
  const values = (name) => (metrics[name] && metrics[name].values) ? metrics[name].values : {};
  const count = (name) => Number(values(name).count || 0);
  const rate = Number(values('login_success_rate').rate || 0);
  const percentile = (name, p) => Number(values(name)[p] || 0);

  const result = {
    vus: VUS,
    duration: DURATION,
    login_attempts: count('login_attempts'),
    csrf_success: count('csrf_success'),
    csrf_failure: count('csrf_failure'),
    auth_attempts: count('auth_attempts'),
    auth_success: count('auth_success'),
    auth_failure: count('auth_failure'),
    login_success_rate: rate,
    http_requests: count('http_reqs'),
    http_failed_rate: Number(values('http_req_failed').rate || 0),
    login_p95_ms: percentile('login_duration', 'p(95)'),
    login_p99_ms: percentile('login_duration', 'p(99)'),
    auth_p95_ms: percentile('auth_duration', 'p(95)'),
    auth_p99_ms: percentile('auth_duration', 'p(99)'),
    login_page_p95_ms: percentile('login_page_duration', 'p(95)'),
    login_page_p99_ms: percentile('login_page_duration', 'p(99)'),
  };

  return {
    stdout: `\nE-UJIAN LOGIN BOTTLENECK DIAGNOSTIC\n===================================\nVUS            : ${VUS}\nDURATION       : ${DURATION}\nLOGIN ATTEMPTS : ${result.login_attempts}\nAUTH SUCCESS   : ${result.auth_success}\nAUTH FAILURE   : ${result.auth_failure}\nSUCCESS RATE   : ${(rate * 100).toFixed(2)}%\nHTTP FAILED    : ${(result.http_failed_rate * 100).toFixed(2)}%\nLOGIN P95      : ${result.login_p95_ms.toFixed(2)} ms\nLOGIN P99      : ${result.login_p99_ms.toFixed(2)} ms\nAUTH P95       : ${result.auth_p95_ms.toFixed(2)} ms\nAUTH P99       : ${result.auth_p99_ms.toFixed(2)} ms\nLOGIN PAGE P95 : ${result.login_page_p95_ms.toFixed(2)} ms\n`,
    [__ENV.K6_SUMMARY_FILE || 'summary.json']: JSON.stringify({ result, metrics }, null, 2),
  };
}
