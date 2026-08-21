import http from 'k6/http';
import { check, fail } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';
const VUS = Number(__ENV.VUS || 100);
const ITERATIONS = Number(__ENV.ITERATIONS || 1);
const USERNAME_PREFIX = __ENV.K6_USERNAME || '';
const PASSWORD_PREFIX = __ENV.K6_PASSWORD || '';

export const options = {
  scenarios: {
    auth_student: {
      executor: 'per-vu-iterations',
      vus: VUS,
      iterations: ITERATIONS,
      maxDuration: '5m',
      gracefulStop: '30s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<3000', 'p(99)<5000'],
    application_errors: ['count<1'],
  },
};

const loginPageDuration = new Trend('login_page_duration', true);
const authDuration = new Trend('auth_duration', true);
const applicationErrors = new Counter('application_errors');

function getStudentCredentials() {
  const id = String(__VU).padStart(3, '0');

  return {
    username: USERNAME_PREFIX ? `${USERNAME_PREFIX}${id}` : id,
    password: PASSWORD_PREFIX ? `${PASSWORD_PREFIX}${id}` : id,
  };
}

function extractCsrf(html) {
  const match = html.match(
    /<input[^>]+name=["']([^"']+)["'][^>]+value=["']([^"']*)["'][^>]*>/i,
  );

  if (!match) {
    return null;
  }

  return {
    name: match[1],
    value: match[2],
  };
}

export default function () {
  const credentials = getStudentCredentials();

  console.log(`VU ${__VU} menggunakan user ${credentials.username}`);

  // ------------------------------------------------------------
  // 1. GET LOGIN PAGE
  // ------------------------------------------------------------
  const loginStart = Date.now();

  const loginPage = http.get(`${BASE_URL}/login`, {
    tags: { endpoint: 'login_page' },
  });

  loginPageDuration.add(Date.now() - loginStart);

  const loginPageOk = check(loginPage, {
    'login status 200': (r) => r.status === 200,
    'login contains csrf': (r) => extractCsrf(r.body || '') !== null,
  });

  if (!loginPageOk) {
    applicationErrors.add(1);
    console.error(`LOGIN PAGE FAILED | user=${credentials.username} | status=${loginPage.status}`);
    fail(`Login page gagal untuk user ${credentials.username}`);
  }

  const csrf = extractCsrf(loginPage.body || '');

  // ------------------------------------------------------------
  // 2. POST AUTHENTICATION
  // ------------------------------------------------------------
  const payload = {
    username: credentials.username,
    password: credentials.password,
  };

  payload[csrf.name] = csrf.value;

  const authStart = Date.now();

  const authResponse = http.post(`${BASE_URL}/auth`, payload, {
    redirects: 0,
    tags: { endpoint: 'auth' },
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'Referer': `${BASE_URL}/login`,
    },
  });

  authDuration.add(Date.now() - authStart);

  const authOk = check(authResponse, {
    'auth returns redirect': (r) => r.status >= 300 && r.status < 400,
    'auth redirects to student profile': (r) => {
      const location = r.headers.Location || r.headers.location || '';
      return location.includes('/siswa/users/profil');
    },
  });

  if (!authOk) {
    applicationErrors.add(1);
    console.error(
      `AUTH FAILED | user=${credentials.username} | status=${authResponse.status} | location=${authResponse.headers.Location || authResponse.headers.location || '-'} | body=${String(authResponse.body || '').slice(0, 300)}`,
    );
    fail(`Authentication gagal untuk user ${credentials.username}`);
  }
}
