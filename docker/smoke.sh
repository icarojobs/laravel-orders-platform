#!/usr/bin/env bash
# End-to-end check of the running stack: API -> Horizon -> RabbitMQ -> notification-service.
set -euo pipefail

BASE_URL=${BASE_URL:-http://localhost:8080}
compose() { docker compose "$@"; }

curl --fail --silent "$BASE_URL/up" > /dev/null
echo "✔ app is up"

if [ "$(compose exec -T postgres psql -U orders -d orders -tAc "SELECT count(*) FROM users WHERE email = 'admin@example.com'")" = "0" ]; then
    compose exec -T app php artisan db:seed --force > /dev/null
    echo "✔ database seeded"
fi
token=$(curl --fail --silent -X POST "$BASE_URL/api/v1/tokens" -H 'Accept: application/json' \
    -d email=admin@example.com -d password=password -d device_name=smoke | sed -E 's/.*"token":"([^"]+)".*/\1/')

customer=$(compose exec -T postgres psql -U orders -d orders -tAc 'SELECT id FROM customers ORDER BY id LIMIT 1')
product=$(compose exec -T postgres psql -U orders -d orders -tAc 'SELECT id FROM products WHERE stock > 0 ORDER BY id LIMIT 1')

number=$(curl --fail --silent -X POST "$BASE_URL/api/v1/orders" \
    -H "Authorization: Bearer $token" -H 'Accept: application/json' -H 'Content-Type: application/json' \
    -d "{\"customer_id\": $customer, \"items\": [{\"product_id\": $product, \"quantity\": 1}]}" \
    | sed -E 's/.*"number":"([^"]+)".*/\1/')
echo "✔ order $number placed through the API"

for _ in $(seq 1 30); do
    found=$(compose exec -T postgres psql -U orders -d notifications -tAc \
        "SELECT count(*) FROM notifications WHERE subject LIKE '%$number%'" 2>/dev/null || echo 0)
    if [ "$found" = "1" ]; then
        echo "✔ notification-service stored the order.placed notification"
        exit 0
    fi
    sleep 1
done

echo "✘ notification for $number not found" >&2
compose logs --tail=50 horizon notification-service >&2
exit 1
