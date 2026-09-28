// Focused scenario used to compare the orders listing before/after the N+1 fix.
//
//   docker run --rm --network host -v "$PWD/loadtest:/scripts" grafana/k6 run /scripts/orders-list.js
import http from 'k6/http';
import { check } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';

export const options = {
    vus: Number(__ENV.VUS || 10),
    duration: __ENV.DURATION || '30s',
    thresholds: {
        http_req_failed: ['rate<0.01'],
    },
};

export function setup() {
    const res = http.post(
        `${BASE_URL}/api/v1/tokens`,
        {
            email: __ENV.EMAIL || 'admin@example.com',
            password: __ENV.PASSWORD || 'password',
            device_name: 'k6',
        },
        { headers: { Accept: 'application/json' } },
    );

    return { token: res.json('token') };
}

export default function ({ token }) {
    const res = http.get(`${BASE_URL}/api/v1/orders?per_page=50`, {
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        },
    });

    check(res, { 'status is 200': (r) => r.status === 200 });
}
