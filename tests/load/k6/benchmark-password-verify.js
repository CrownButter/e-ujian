import http from 'k6/http';
import { check } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';
const PASSWORD = __ENV.PASSWORD || '';
const HASH = __ENV.HASH || '';

const passwordVerifyDuration = new Trend('password_verify_duration');
const passwordVerifySuccess = new Counter('password_verify_success');
const passwordVerifyFailure = new Counter('password_verify_failure');

export const options = {
    scenarios: {
        password_verify_test: {
            executor: 'shared-iterations',
            vus: Number(__ENV.VUS || 1),
            iterations: Number(__ENV.ITERATIONS || 1),
            maxDuration: '5m',
        },
    },

    thresholds: {
        password_verify_duration: ['p(95)<1000'],
    },
};

export function setup() {
    if (!PASSWORD) {
        throw new Error('PASSWORD belum diberikan. Gunakan -e PASSWORD="..."');
    }

    if (!HASH) {
        throw new Error('HASH belum diberikan. Gunakan -e HASH="..."');
    }

    return {};
}

export default function () {
    const response = http.post(
        `${BASE_URL}/benchmark/password-verify`,
        {
            password: PASSWORD,
            hash: HASH,
        },
        {
            tags: {
                test: 'password_verify',
            },
        }
    );

    let duration = null;
    let valid = false;

    try {
        const body = response.json();

        if (body && body.duration_ms !== undefined) {
            duration = Number(body.duration_ms);
            passwordVerifyDuration.add(duration);
        }

        valid = body && body.valid === true;

        if (valid) {
            passwordVerifySuccess.add(1);
        } else {
            passwordVerifyFailure.add(1);
        }
    } catch (e) {
        passwordVerifyFailure.add(1);
    }

    check(response, {
        'HTTP 200': (r) => r.status === 200,
        'password verification measured': () => duration !== null,
        'password valid': () => valid === true,
    });
}
