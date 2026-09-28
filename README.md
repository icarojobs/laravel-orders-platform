# Laravel Orders Platform

[![ci](https://github.com/icarojobs/laravel-orders-platform/actions/workflows/ci.yml/badge.svg)](https://github.com/icarojobs/laravel-orders-platform/actions/workflows/ci.yml)

Plataforma de gestão de pedidos no estilo ERP: clientes, produtos com estoque, pedidos com ciclo de vida
(pendente → pago → enviado → entregue / cancelado), API REST, dashboard de vendas e um microsserviço de
notificações que consome os eventos de pedido via RabbitMQ.

Projeto pessoal de estudo e portfólio, construído em PRs pequenos e semânticos — o histórico de
[pull requests](https://github.com/icarojobs/laravel-orders-platform/pulls?q=is%3Apr+is%3Aclosed) mostra a evolução.

## Stack

| Camada | Tecnologias |
|---|---|
| Backend | PHP 8.5, Laravel 13, Eloquent ORM, Query Builder, Fortify (login), Sanctum (tokens da API), Policies, Form Requests, API Resources |
| Frontend | Inertia.js 3 + React 19 + TypeScript + Tailwind CSS 4; um componente Livewire 4 (estoque baixo) |
| Dados | PostgreSQL 18, Redis 8 (cache, sessão e filas) |
| Mensageria | Horizon (fila `events` no Redis) → RabbitMQ 4 (exchange `orders.events`) → `notification-service` |
| Qualidade | Pest 5 (unit + feature, sobre PHPUnit 13), PHPUnit no microsserviço, Playwright (e2e), Larastan nível 7, Laravel Pint, k6 |
| Infra | Docker multi-stage (nginx + php-fpm), Docker Compose, GitHub Actions |

## Arquitetura

```
                ┌──────────────┐     ┌──────────────────────────┐      ┌────────────┐
 navegador ───► │    nginx     │ ──► │ php-fpm (Laravel)        │ ───► │ PostgreSQL │
 cliente API    └──────────────┘     │  Inertia/React, Livewire │      │  orders    │
                                     │  API REST /api/v1        │ ◄──► │ Redis      │
                                     └────────────┬─────────────┘      └────────────┘
                                                  │ OrderPlaced / OrderShipped
                                                  ▼ (listener em fila)
                                     ┌──────────────────────────┐
                                     │ Horizon (fila "events")  │
                                     └────────────┬─────────────┘
                                                  │ order.placed / order.shipped
                                                  ▼
                                     ┌──────────────────────────┐      ┌───────────────┐
                                     │ RabbitMQ  orders.events  │ ───► │ notification- │ ──► PostgreSQL
                                     │ (topic) + DLQ            │      │ service (PHP) │     notifications
                                     └──────────────────────────┘      └───────────────┘
```

Organização do código (DDD-lite):

```
app/
├── Domain/
│   ├── Orders/        # enum de status e transições, DTOs, serviços, eventos, exceções, OrderSearch
│   └── Reports/       # SalesReport (Query Builder + cache no Redis)
├── Infrastructure/
│   └── Messaging/     # publicadores RabbitMQ/log e o contrato da mensagem
├── Http/              # controllers finos (API v1 e Web), Form Requests, Resources
├── Livewire/          # LowStockProducts
├── Policies/          # OrderPolicy (papéis + abilities do token)
└── Models/            # Eloquent
notification-service/  # microsserviço consumidor (PHP puro, container próprio)
loadtest/              # cenários k6
benchmarks/            # medição de queries e latência da API
```

Decisões principais:

- **Regras no domínio, controllers finos.** `PlaceOrderService` trava os produtos com `SELECT … FOR UPDATE`,
  valida e baixa o estoque na mesma transação; `Order::transitionTo()` impede transições inválidas.
- **Dependências invertidas.** `OrderNumberGenerator` e `OrderEventPublisher` são contratos resolvidos pelo
  container (RabbitMQ no Docker, log/spy nos testes).
- **Eventos depois do commit.** `OrderPlaced`/`OrderShipped` implementam `ShouldDispatchAfterCommit`; um listener
  em fila publica no broker com retry e backoff.
- **Consumidor idempotente.** O `notification-service` grava cada `event_id` uma única vez, manda mensagens
  inválidas para a DLQ e reenfileira falhas inesperadas uma vez.
- **N+1 bloqueado.** `Model::preventLazyLoading()` fora de produção e um teste que fixa a quantidade de queries da listagem.

## Como rodar

Pré-requisito: Docker com Compose v2.

```bash
git clone https://github.com/icarojobs/laravel-orders-platform.git
cd laravel-orders-platform
docker compose up -d --build --wait
docker compose exec app php artisan db:seed --force   # -e SEED_ORDERS=100000 para um volume maior
```

| Serviço | Endereço |
|---|---|
| Aplicação | http://localhost:8080 — `admin@example.com` / `password` (ou `viewer@example.com`, só leitura) |
| Horizon | http://localhost:8080/horizon (admin) |
| RabbitMQ | http://localhost:15672 — `orders` / `secret` |
| PostgreSQL | `localhost:54329` — `orders` / `secret` |

As portas podem ser trocadas com `APP_PORT`, `DB_FORWARD_PORT` e `RABBITMQ_UI_PORT`.

Para conferir o fluxo inteiro (API → Horizon → RabbitMQ → notification-service):

```bash
docker/smoke.sh
```

## API

Autenticação por token do Sanctum. Viewers recebem só a ability `orders:read`; cancelar pedido é exclusivo de admin.

| Método | Rota | Descrição |
|---|---|---|
| `POST` | `/api/v1/tokens` | emite token (`email`, `password`, `device_name`) |
| `DELETE` | `/api/v1/tokens/current` | revoga o token atual |
| `GET` | `/api/v1/orders` | lista paginada; filtros `status`, `customer_id`, `search`, `from`, `to`, `per_page` |
| `POST` | `/api/v1/orders` | cria pedido (`customer_id`, `items[].product_id`, `items[].quantity`) |
| `GET` | `/api/v1/orders/{id}` | detalhe com cliente e itens |
| `PATCH` | `/api/v1/orders/{id}/status` | muda o status (`paid`, `shipped`, `delivered`, `cancelled`) |
| `GET` | `/api/v1/reports/sales` | resumo, faturamento por dia, pedidos por status e top produtos (`from`, `to`) |

```bash
TOKEN=$(curl -s -X POST localhost:8080/api/v1/tokens -H 'Accept: application/json' \
  -d email=admin@example.com -d password=password -d device_name=cli | jq -r .token)

curl -s 'localhost:8080/api/v1/orders?status=paid&per_page=5' \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' | jq
```

Regras de negócio violadas (estoque insuficiente, transição inválida) retornam `422` com mensagem.

## Testes e qualidade

Tudo roda dentro dos containers, do mesmo jeito que no CI:

```bash
docker compose run --rm test                                   # Pest (unit + feature) contra PostgreSQL
docker compose run --rm test vendor/bin/pest --coverage        # com cobertura (PCOV)
docker compose run --rm test vendor/bin/pint --test
docker compose run --rm test vendor/bin/phpstan analyse        # Larastan nível 7
docker compose run --rm notification-service-test             # PHPUnit do microsserviço
npm ci && npx playwright install chromium && npm run test:e2e  # Playwright contra o stack no ar
```

O pipeline do GitHub Actions roda Pint + Larastan, Pest com cobertura mínima de 80% em PostgreSQL,
checagem de tipos e build do front, PHPUnit do microsserviço, build das imagens Docker com smoke test
end-to-end e a suíte Playwright contra o `docker compose`. Há também um workflow manual (`load-test`) com k6.

## Resultados

Números medidos localmente, em uma execução de cada cenário, com o stack do `docker compose`
(imagem `dev`, `APP_ENV=production`, OPcache ligado).

**Máquina:** Intel Core i7-10700 @ 2.90 GHz (8 núcleos / 16 threads), 31 GiB de RAM, Pop!_OS 22.04
(kernel 7.1.1), Docker 29.8.1 / Compose v5.5.1.

### Testes

| Suíte | Resultado |
|---|---|
| Aplicação (Pest + testes PHPUnit do starter kit) | 118 testes, 413 asserções, **97,7% de cobertura de linhas** |
| notification-service (PHPUnit) | 22 testes, 51 asserções, 77,4% de cobertura de linhas |
| E2E (Playwright, Chromium) | 9 testes (incluindo o setup de autenticação) |

```bash
docker compose run --rm test vendor/bin/pest --coverage
docker compose run --rm notification-service-test vendor/bin/phpunit --coverage-text
```

### Otimização: N+1 na listagem de pedidos (#9)

A primeira versão de `GET /api/v1/orders` carregava cliente, itens e produtos de forma preguiçosa, e o
PostgreSQL não tinha índice em `order_items.order_id` (FK não ganha índice automaticamente). A correção
foi eager loading (`with(['customer', 'items.product'])`) e índices para os filtros da listagem.

Base com 100.000 pedidos e 250.444 itens; `GET /api/v1/orders?per_page=50`, 30 requisições por versão:

| Versão | Queries por requisição | p50 | p95 |
|---|---|---|---|
| Antes (lazy loading, sem índices) | 234 | 996,8 ms | 1.212,4 ms |
| Eager loading | 7 | 63,3 ms | 114,1 ms |
| Eager loading + índices | 7 | 56,7 ms | 61,1 ms |

Com k6 (10 VUs por 30 s na mesma rota): **5,65 req/s e p95 de 2,09 s antes → 44,76 req/s e p95 de 258 ms depois**,
0% de erros nos dois casos.

```bash
docker compose exec -e SEED_ORDERS=100000 app php artisan migrate:fresh --seed --force
docker compose exec app php benchmarks/orders-api.php 30
docker run --rm --network host -v "$PWD/loadtest:/scripts" grafana/k6 run /scripts/orders-list.js
```

### Teste de carga (k6)

Cenário `loadtest/orders-api.js`: rampa de 15 s até 50 VUs, 60 s estáveis e 10 s de descida, misturando
listagem (50%), filtro por status (25%), detalhe (20%) e relatório de vendas (5%). Base com 100.000 pedidos.

| Pool do php-fpm | Requisições | Throughput | p50 | p95 | Erros | Threshold p95 < 500 ms |
|---|---|---|---|---|---|---|
| Padrão da imagem (5 workers) | 8.148 | 95,4 req/s | 493,7 ms | 578,6 ms | 0% | reprovado |
| Ajustado (32 workers, #16) | 13.070 | 153,1 req/s | 282,7 ms | 462,5 ms | 0% | aprovado |

```bash
docker run --rm --network host -v "$PWD/loadtest:/scripts" grafana/k6 run /scripts/orders-api.js
```

## Licença

[MIT](LICENSE)
