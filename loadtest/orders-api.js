// k6 load test for the orders API.
//
//   docker run --rm --network host -v "$PWD/loadtest:/scripts" grafana/k6 run /scripts/orders-api.js
//
// BASE_URL, EMAIL, PASSWORD, VUS and DURATION can be overridden with -e.
import http from 'k6/http';
import { check, group } from 'k6';
import { Trend } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';
const STATUSES = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];

const listDuration = new Trend('orders_list_duration', true);
const showDuration = new Trend('orders_show_duration', true);
const reportDuration = new Trend('sales_report_duration', true);

export const options = {
    scenarios: {
        browse: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '15s', target: Number(__ENV.VUS || 50) },
                {
                    duration: __ENV.DURATION || '60s',
                    target: Number(__ENV.VUS || 50),
                },
                { duration: '10s', target: 0 },
            ],
        },
    },
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<500'],
        checks: ['rate>0.99'],
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
    check(res, { 'token issued': (r) => r.status === 201 });

    const params = {
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${res.json('token')}`,
        },
    };
    const ids = http
        .get(`${BASE_URL}/api/v1/orders?per_page=100`, params)
        .json('data')
        .map((order) => order.id);

    return { params, ids };
}

export default function ({ params, ids }) {
    const roll = Math.random();

    if (roll < 0.5) {
        group('list orders', () => {
            const page = 1 + Math.floor(Math.random() * 20);
            const res = http.get(
                `${BASE_URL}/api/v1/orders?per_page=20&page=${page}`,
                { ...params, tags: { name: 'GET /orders' } },
            );
            listDuration.add(res.timings.duration);
            check(res, { 'list 200': (r) => r.status === 200 });
        });
    } else if (roll < 0.75) {
        group('filter orders', () => {
            const status =
                STATUSES[Math.floor(Math.random() * STATUSES.length)];
            const res = http.get(
                `${BASE_URL}/api/v1/orders?per_page=20&status=${status}`,
                {
                    ...params,
                    tags: { name: 'GET /orders?status' },
                },
            );
            listDuration.add(res.timings.duration);
            check(res, { 'filter 200': (r) => r.status === 200 });
        });
    } else if (roll < 0.95) {
        group('show order', () => {
            const id = ids[Math.floor(Math.random() * ids.length)];
            const res = http.get(`${BASE_URL}/api/v1/orders/${id}`, {
                ...params,
                tags: { name: 'GET /orders/:id' },
            });
            showDuration.add(res.timings.duration);
            check(res, { 'show 200': (r) => r.status === 200 });
        });
    } else {
        group('sales report', () => {
            const res = http.get(`${BASE_URL}/api/v1/reports/sales`, {
                ...params,
                tags: { name: 'GET /reports/sales' },
            });
            reportDuration.add(res.timings.duration);
            check(res, { 'report 200': (r) => r.status === 200 });
        });
    }
}
