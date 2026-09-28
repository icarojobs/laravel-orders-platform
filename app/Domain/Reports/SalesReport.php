<?php

namespace App\Domain\Reports;

use App\Domain\Orders\Enums\OrderStatus;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

/**
 * Aggregations built with the query builder so they stay single round-trip
 * queries instead of hydrating thousands of models.
 */
class SalesReport
{
    private const TTL_SECONDS = 600;

    public const VERSION_KEY = 'reports:sales:version';

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly Cache $cache,
    ) {}

    /**
     * @return array{period: array{from: string, to: string}, summary: array{orders: int, revenue_cents: int, average_ticket_cents: int}, by_status: array<string, int>, by_day: array<int, array{day: string, orders: int, revenue_cents: int}>, top_products: array<int, array{sku: string, name: string, units: int, revenue_cents: int}>}
     */
    public function for(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $from = $from->startOfDay();
        $to = $to->endOfDay();
        $version = (int) $this->cache->get(self::VERSION_KEY, 1);
        $key = sprintf('reports:sales:v%d:%s:%s', $version, $from->toDateString(), $to->toDateString());

        return $this->cache->remember($key, self::TTL_SECONDS, fn () => [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary' => $this->summary($from, $to),
            'by_status' => $this->byStatus($from, $to),
            'by_day' => $this->byDay($from, $to),
            'top_products' => $this->topProducts($from, $to),
        ]);
    }

    public function flush(): void
    {
        if (! $this->cache->has(self::VERSION_KEY)) {
            $this->cache->forever(self::VERSION_KEY, 1);
        }

        $this->cache->increment(self::VERSION_KEY);
    }

    /**
     * @return array{orders: int, revenue_cents: int, average_ticket_cents: int}
     */
    private function summary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $row = $this->revenueOrders($from, $to)
            ->selectRaw('COUNT(*) AS orders, COALESCE(SUM(total_cents), 0) AS revenue')
            ->first();

        $orders = (int) ($row->orders ?? 0);
        $revenue = (int) ($row->revenue ?? 0);

        return [
            'orders' => $orders,
            'revenue_cents' => $revenue,
            'average_ticket_cents' => $orders > 0 ? intdiv($revenue, $orders) : 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function byStatus(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->db->table('orders')
            ->whereBetween('placed_at', [$from, $to])
            ->groupBy('status')
            ->orderBy('status')
            ->selectRaw('status, COUNT(*) AS total')
            ->pluck('total', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * @return array<int, array{day: string, orders: int, revenue_cents: int}>
     */
    private function byDay(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->revenueOrders($from, $to)
            ->selectRaw('DATE(placed_at) AS day, COUNT(*) AS orders, SUM(total_cents) AS revenue')
            ->groupByRaw('DATE(placed_at)')
            ->orderBy('day')
            ->get()
            ->map(fn (object $row) => [
                'day' => (string) $row->day,
                'orders' => (int) $row->orders,
                'revenue_cents' => (int) $row->revenue,
            ])
            ->all();
    }

    /**
     * @return array<int, array{sku: string, name: string, units: int, revenue_cents: int}>
     */
    private function topProducts(CarbonImmutable $from, CarbonImmutable $to, int $limit = 5): array
    {
        return $this->db->table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereBetween('orders.placed_at', [$from, $to])
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->groupBy('products.id', 'products.sku', 'products.name')
            ->selectRaw('products.sku, products.name, SUM(order_items.quantity) AS units, SUM(order_items.total_cents) AS revenue')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn (object $row) => [
                'sku' => (string) $row->sku,
                'name' => (string) $row->name,
                'units' => (int) $row->units,
                'revenue_cents' => (int) $row->revenue,
            ])
            ->all();
    }

    private function revenueOrders(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return $this->db->table('orders')
            ->whereBetween('placed_at', [$from, $to])
            ->where('status', '!=', OrderStatus::Cancelled->value);
    }
}
