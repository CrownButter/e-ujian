import http from 'k6/http';
import { check } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const TOTAL_USERS = parsePositiveInt(__ENV.TOTAL_USERS, 709);
const MAX_WAIT_SECONDS = parsePositiveInt(__ENV.MAX_WAIT_SECONDS, 300);
const POLL_INTERVAL_SECONDS = parsePositiveInt(__ENV.POLL_INTERVAL_SECONDS, 2);
const SUCCESS_PATH = '/siswa/users/profil';

if (TOTAL_USERS > 709) {
  throw new Error(`TOTAL_USERS cannot exceed 709. Received: ${TOTAL_USERS}`);
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
const loginPageSuccess = new Counter('login_page_success');
const waitingRoomAttempts = new Counter('waiting_room_attempts');
const waitingRoomSuccess = new Counter('waiting_room_success');
const waitingRoomErrors = new Counter('waiting_room_errors');
const queueReady = new Counter('queue_ready');
const queueExpired = new Counter('queue_expired');
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

  for (const pattern of patterns) {
    const match = html.match(pattern);
    if (!match) continue;

    if (pattern === patterns[0]) {
      return { name: match[1], value: match[2] };
    }

    return { name: match[2], value: match[1] };
  }

  return null;
}

function accountForVU() {
  if (__VU < 1 || __VU > TOTAL_USERS) {
    throw new Error(`Unexpected VU number: ${__VU}`);
  }
  return String(__VU).padStart(3, '0');
}

function markHttpFailure(response) {
  httpFailedRate.add(!response || response.status === 0 || response.status >= 500);
}

export default function () {
  const username = accountForVU();
  const loginStartedAt = Date.now();
  loginAttempts.add(1);

  const loginPage = http.get(`${BASE_URL}/login`, {
    redirects: 0,
    tags: { request: 'login_page', username },
  });
  loginPageDuration.add(loginPage.timings.duration);
  markHttpFailure(loginPage);

  const loginPageOk = check(loginPage, {
    'GET /login status 200': (r) => r.status === 200,
    'login CSRF token exists': (r) => extractCsrf(r.body || '') !== null,
  });

  if (!loginPageOk) {
    loginSuccessRate.add(false);
    return;
  }

  loginPageSuccess.add(1);

  const csrf = extractCsrf(loginPage.body || '');
  if (!csrf || !csrf.value) {
    loginSuccessRate.add(false);
    return;
  }

  const waitingStartedAt = Date.now();
  waitingRoomAttempts.add(1);

  const enterResponse = http.post(
    `${BASE_URL}/waiting-room/enter`,
    { [csrf.name]: csrf.value },
    {
      redirects: 0,
      tags: { request: 'waiting_room_enter', username },
    }
  );
  waitingRoomDuration.add(enterResponse.timings.duration);
  markHttpFailure(enterResponse);

  let enterData = null;
  try {
    enterData = JSON.parse(enterResponse.body || '{}');
  } catch (_) {
    enterData = null;
  }

  if (enterResponse.status !== 200 || !enterData?.ok || !enterData.ticket) {
    waitingRoomErrors.add(1);
    loginSuccessRate.add(false);
    console.error(`[WAITING ROOM ENTER FAILED] username=${username} status=${enterResponse.status} body=${(enterResponse.body || '').slice(0, 300)}`);
    return;
  }

  waitingRoomSuccess.add(1);

  const ticket = enterData.ticket;
  let status = enterData.status;
  let position = Number(enterData.position || 0);
  let latestCsrf = enterData.csrf_token || csrf.value;
  const deadline = Date.now() + MAX_WAIT_SECONDS * 1000;

  while (status !== 'ready' && Date.now() < deadline) {
    httpFailedRate.add(false);
    const response = http.get(
      `${BASE_URL}/waiting-room/status?ticket=${encodeURIComponent(ticket)}`,
      {
        redirects: 0,
        tags: { request: 'waiting_room_status', username },
      }
    );
    markHttpFailure(response);

    let data = null;
    try {
      data = JSON.parse(response.body || '{}');
    } catch (_) {
      data = null;
    }

    if (response.status === 410 || data?.expired) {
      queueExpired.add(1);
      loginSuccessRate.add(false);
      console.error(`[QUEUE EXPIRED] username=${username} position=${position}`);
      return;
    }

    if (response.status !== 200 || !data?.ok) {
      waitingRoomErrors.add(1);
      break;
    }

    status = data.status;
    position = Number(data.position || 0);
    if (data.csrf_token) {
      latestCsrf = data.csrf_token;
    }

    if (status !== 'ready') {
      const waitMs = Math.max(1000, Number(data.retry_after || POLL_INTERVAL_SECONDS) * 1000);
      http.batch; // keep k6 parser/runtime compatible without introducing sleep-heavy imports
      const end = Date.now() + waitMs;
      while (Date.now() < end) {
        // Busy waiting is intentionally avoided below; k6's sleep is imported lazily.
        break;
      }
      const sleepSeconds = waitMs / 1000;
      if (sleepSeconds > 0) {
        // eslint-disable-next-line no-undef
        sleepForPolling(sleepSeconds);
      }
    }
  }

  if (status !== 'ready') {
    loginSuccessRate.add(false);
    console.error(`[QUEUE TIMEOUT] username=${username} position=${position} waited_ms=${Date.now() - waitingStartedAt}`);
    return;
  }

  queueReady.add(1);
  queueWaitDuration.add(Date.now() - waitingStartedAt);

  authAttempts.add(1);
  const authResponse = http.post(
    `${BASE_URL}/auth`,
    {
      username,
      password: username,
      [csrf.name]: latestCsrf,
      waiting_room_ticket: ticket,
    },
    {
      redirects: 0,
      tags: { request: 'auth', username },
    }
  );
  authDuration.add(authResponse.timings.duration);
  markHttpFailure(authResponse);

  const location = authResponse.headers.Location || authResponse.headers.location || '';
  const success = check(authResponse, {
    'POST /auth returns redirect': (r) => r.status >= 300 && r.status < 400,
    'POST /auth redirects to student profile': () => location.includes(SUCCESS_PATH),
  });

  loginDuration.add(Date.now() - loginStartedAt);
  loginSuccessRate.add(success);

  if (success) {
    authSuccess.add(1);
  } else {
    authFailure.add(1);
    console.error(`[AUTH FAILED] username=${username} status=${authResponse.status} location=${location || '<none>'} body=${(authResponse.body || '').slice(0, 200)}`);
  }
}

// k6 does not expose a zero-cost sleep primitive; import is kept isolated here
// so the main flow remains easy to inspect.
import { sleep as sleepForPolling } from 'k6';

export function handleSummary(data) {
  const metrics = data.metrics || {};
  const count = (name) => metrics[name]?.values?.count || 0;
  const rate = (name) => metrics[name]?.values?.rate || 0;

  const attempts = count('login_attempts');
  const pageSuccess = count('login_page_success');
  const enterAttempts = count('waiting_room_attempts');
  const enterSuccess = count('waiting_room_success');
  const waitingErrors = count('waiting_room_errors');
  const ready = count('queue_ready');
  const expired = count('queue_expired');
  const authTry = count('auth_attempts');
  const success = count('auth_success');
  const failure = count('auth_failure');

  const report = {
    test: {
      name: 'E-UJIAN K6 waiting room batch login',
      base_url: BASE_URL,
      total_users: TOTAL_USERS,
      max_wait_seconds: MAX_WAIT_SECONDS,
      poll_interval_seconds: POLL_INTERVAL_SECONDS,
      account_range: `001-${String(TOTAL_USERS).padStart(3, '0')}`,
      executor: 'per-vu-iterations',
      generated_at: new Date().toISOString(),
    },
    result: {
      login_attempts: attempts,
      login_page_success: pageSuccess,
      waiting_room_attempts: enterAttempts,
      waiting_room_success: enterSuccess,
      waiting_room_errors: waitingErrors,
      queue_ready: ready,
      queue_expired: expired,
      auth_attempts: authTry,
      auth_success: success,
      auth_failure: failure,
      login_success_rate: rate('login_success_rate'),
      http_failed_rate: rate('http_failed_rate'),
    },
    metrics: data.metrics,
  };

  const trend = (name, field) => metrics[name]?.values?.[field] ?? 0;

  return {
    stdout: `\nE-UJIAN WAITING ROOM BATCH LOGIN TEST\n====================================\nBASE_URL             : ${BASE_URL}\nTOTAL USERS          : ${TOTAL_USERS}\nACCOUNT RANGE        : 001-${String(TOTAL_USERS).padStart(3, '0')}\nMAX QUEUE WAIT       : ${MAX_WAIT_SECONDS}s\nPOLL INTERVAL        : ${POLL_INTERVAL_SECONDS}s\nGET /login           : ${attempts}\nLOGIN PAGE SUCCESS   : ${pageSuccess}\nWAITING ENTER        : ${enterAttempts}\nWAITING ENTER OK     : ${enterSuccess}\nQUEUE READY          : ${ready}\nQUEUE EXPIRED        : ${expired}\nWAITING ROOM ERRORS  : ${waitingErrors}\nPOST /auth           : ${authTry}\nAUTH SUCCESS         : ${success}\nAUTH FAILURE         : ${failure}\nLOGIN SUCCESS RATE   : ${(rate('login_success_rate') * 100).toFixed(2)}%\nHTTP FAILED RATE     : ${(rate('http_failed_rate') * 100).toFixed(2)}%\nQUEUE WAIT P95       : ${trend('queue_wait_duration', 'p(95)')} ms\nAUTH P95             : ${trend('auth_duration', 'p(95)')} ms\nLOGIN P95            : ${trend('login_duration', 'p(95)')} ms\nLOGIN P99            : ${trend('login_duration', 'p(99)')} ms\n`,
    [__ENV.K6_SUMMARY_FILE || 'summary.json']: JSON.stringify(report, null, 2),
  };
}
