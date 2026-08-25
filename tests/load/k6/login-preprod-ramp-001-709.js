import http from 'k6/http';
import { check } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const BASE_URL = (__ENV.BASE_URL || 'https://e-ujian.bayatmultijaya.com').replace(/\/$/, '');
const LOGIN_PATH = '/login';
const AUTH_PATH = '/auth';
const SUCCESS_PATH = '/siswa/users/profil';
const MAX_ACCOUNT = 709;
const VUS = parsePositiveInt(__ENV.VUS, 100);

if (VUS > MAX_ACCOUNT) {
  throw new Error(`VUS cannot exceed ${MAX_ACCOUNT}. Received: ${VUS}`);
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
const authHttpUnexpected = new Counter('auth_http_unexpected');
const authRedirectWrong = new Counter('auth_redirect_wrong');
const loginSuccessRate = new Rate('login_success_rate');
const loginDuration = new Trend('login_duration', true);
const loginPageDuration = new Trend('login_page_duration', true);
const authDuration = new Trend('auth_duration', true);

export const options = {
  scenarios: {
    login_once_per_vu: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '10s', target: Math.min(10, VUS) },
        { duration: '15s', target: Math.min(25, VUS) },
        { duration: '15s', target: Math.min(50, VUS) },
        { duration: '15s', target: Math.min(75, VUS) },
        { duration: '15s', target: VUS },
        { duration: '30s', target: VUS },
        { duration: '10s', target: 0 },
      ],
      gracefulRampDown: '10s',
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

function metricFailure(message, username) {
  console.error(`[PREPROD LOGIN DIAGNOSTIC] username=${username} ${message}`);
}

export default function () {
  if (__ITER > 0) return;

  const username = accountForVU();
  const password = username;
  const startedAt = Date.now();
  loginAttempts.add(1, { username });

  const loginPage = http.get(`${BASE_URL}${LOGIN_PATH}`, {
    redirects: 0,
    tags: { request: 'login_page', username },
  });
  loginPageDuration.add(loginPage.timings.duration, { username });

  const csrf = extractCsrf(loginPage.body || '');
  const pageOk = check(loginPage, {
    'login page status is 200': (r) => r.status === 200,
    'CSRF token exists': () => csrf !== null && csrf.value.length > 0,
  });

  if (!pageOk || !csrf) {
    csrfFailure.add(1, { username });
    loginSuccessRate.add(false, { username });
    loginDuration.add(Date.now() - startedAt, { username });
    metricFailure(
      `login_page_failed status=${loginPage.status} error=${loginPage.error || '<none>'}`,
      username
    );
    return;
  }

  csrfSuccess.add(1, { username });
  authAttempts.add(1, { username });

  const payload = {
    username,
    password,
    [csrf.name]: csrf.value,
  };

  const authResponse = http.post(`${BASE_URL}${AUTH_PATH}`, payload, {
    redirects: 0,
    tags: { request: 'auth', username },
  });
  authDuration.add(authResponse.timings.duration, { username });

  const location = authResponse.headers.Location || authResponse.headers.location || '';
  const isRedirect = authResponse.status >= 300 && authResponse.status < 400;
  const isExpectedRedirect = location.includes(SUCCESS_PATH);
  const success = isRedirect && isExpectedRedirect;

  check(authResponse, {
    'auth returns redirect': () => isRedirect,
    'auth redirects to expected destination': () => isExpectedRedirect,
  });

  loginDuration.add(Date.now() - startedAt, { username });
  loginSuccessRate.add(success, { username });

  if (success) {
    authSuccess.add(1, { username });
    return;
  }

  authFailure.add(1, { username });
  if (!isRedirect) {
    authHttpUnexpected.add(1, { username });
  } else if (!isExpectedRedirect) {
    authRedirectWrong.add(1, { username });
  }

  metricFailure(
    `auth_failed status=${authResponse.status} location=${location || '<none>'} ` +
    `serverTiming=${JSON.stringify(authResponse.timings)} error=${authResponse.error || '<none>'}`,
    username
  );
}

export function handleSummary(data) {
  const metrics = data.metrics || {};
  const count = (name) => metrics[name]?.values?.count || 0;
  const rate = metrics.login_success_rate?.values?.rate || 0;
  const totalHttpRequests = count('login_attempts') + count('auth_attempts');

  const report = {
    test: {
      name: 'E-UJIAN preprod ramp-up direct login diagnostic',
      base_url: BASE_URL,
      configured_vus: VUS,
      account_range: `001-${String(VUS).padStart(3, '0')}`,
      executor: 'ramping-vus',
      iteration_policy: 'one login attempt per VU; repeat iterations ignored',
      waiting_room: 'disabled / not used',
      generated_at: new Date().toISOString(),
    },
    requests: {
      get_login: count('login_attempts'),
      post_auth: count('auth_attempts'),
      total_http_requests: totalHttpRequests,
    },
    result: {
      login_attempts: count('login_attempts'),
      csrf_success: count('csrf_success'),
      csrf_failure: count('csrf_failure'),
      auth_attempts: count('auth_attempts'),
      auth_success: count('auth_success'),
      auth_failure: count('auth_failure'),
      auth_http_unexpected: count('auth_http_unexpected'),
      auth_redirect_wrong: count('auth_redirect_wrong'),
      login_success_rate: rate,
    },
    metrics: data.metrics,
  };

  return {
    stdout:
      `\nE-UJIAN K6 PREPROD RAMP-UP DIRECT LOGIN DIAGNOSTIC\n` +
      `===================================================\n` +
      `BASE_URL             : ${BASE_URL}\n` +
      `CONFIGURED VUS       : ${VUS}\n` +
      `ACCOUNT RANGE        : 001-${String(VUS).padStart(3, '0')}\n` +
      `ITERATION POLICY     : one attempt per VU\n` +
      `WAITING ROOM         : NOT USED\n` +
      `GET /login           : ${count('login_attempts')}\n` +
      `CSRF SUCCESS         : ${count('csrf_success')}\n` +
      `CSRF FAILURE         : ${count('csrf_failure')}\n` +
      `POST /auth           : ${count('auth_attempts')}\n` +
      `AUTH SUCCESS         : ${count('auth_success')}\n` +
      `AUTH FAILURE         : ${count('auth_failure')}\n` +
      `AUTH HTTP UNEXPECTED : ${count('auth_http_unexpected')}\n` +
      `AUTH REDIRECT WRONG  : ${count('auth_redirect_wrong')}\n` +
      `TOTAL HTTP REQ       : ${totalHttpRequests}\n` +
      `SUCCESS RATE         : ${(rate * 100).toFixed(2)}%\n`,
    [__ENV.K6_SUMMARY_FILE || 'preprod-ramp-summary.json']:
      JSON.stringify(report, null, 2),
  };
}
