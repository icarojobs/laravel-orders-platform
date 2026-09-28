import { Head, router } from '@inertiajs/react';
import { OrderStatusBadge } from '@/components/order-status-badge';
import { Button } from '@/components/ui/button';
import { formatDateTime, formatMoney } from '@/lib/format';
import { index, status as updateStatus } from '@/routes/orders';
import type { Option, Order, OrderStatus } from '@/types';

type Props = {
    order: { data: Order };
    transitions: Option<OrderStatus>[];
};

const actionLabels: Record<OrderStatus, string> = {
    pending: 'Reabrir',
    paid: 'Confirmar pagamento',
    shipped: 'Marcar como enviado',
    delivered: 'Marcar como entregue',
    cancelled: 'Cancelar pedido',
};

export default function OrderShow({
    order: { data: order },
    transitions,
}: Props) {
    const move = (target: OrderStatus) => {
        router.patch(
            updateStatus.url(order.id),
            { status: target },
            { preserveScroll: true },
        );
    };

    const timeline = [
        { label: 'Criado', at: order.placed_at },
        { label: 'Pago', at: order.paid_at },
        { label: 'Enviado', at: order.shipped_at },
        { label: 'Entregue', at: order.delivered_at },
        { label: 'Cancelado', at: order.cancelled_at },
    ].filter((step) => step.at !== null);

    return (
        <>
            <Head title={`Pedido ${order.number}`} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1
                            className="text-xl font-semibold"
                            data-testid="order-number"
                        >
                            {order.number}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {order.customer.name} · {order.customer.email}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <OrderStatusBadge
                            status={order.status}
                            label={order.status_label}
                        />
                        {transitions.map((transition) => (
                            <Button
                                key={transition.value}
                                size="sm"
                                variant={
                                    transition.value === 'cancelled'
                                        ? 'destructive'
                                        : 'default'
                                }
                                onClick={() => move(transition.value)}
                            >
                                {actionLabels[transition.value]}
                            </Button>
                        ))}
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 lg:col-span-2 dark:border-sidebar-border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left text-xs text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3">SKU</th>
                                    <th className="px-4 py-3">Produto</th>
                                    <th className="px-4 py-3 text-right">
                                        Qtd.
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Preço
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {order.items.map((item) => (
                                    <tr
                                        key={item.id}
                                        className="border-t border-sidebar-border/70"
                                    >
                                        <td className="px-4 py-3 font-mono text-xs">
                                            {item.sku}
                                        </td>
                                        <td className="px-4 py-3">
                                            {item.product}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {item.quantity}
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {formatMoney(item.unit_price_cents)}
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {formatMoney(item.total_cents)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr className="border-t border-sidebar-border/70 font-semibold">
                                    <td
                                        colSpan={4}
                                        className="px-4 py-3 text-right"
                                    >
                                        Total
                                    </td>
                                    <td
                                        className="px-4 py-3 text-right tabular-nums"
                                        data-testid="order-total"
                                    >
                                        {formatMoney(order.total_cents)}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h2 className="mb-3 text-sm font-semibold">
                            Histórico
                        </h2>
                        <ol className="space-y-2 text-sm">
                            {timeline.map((step) => (
                                <li
                                    key={step.label}
                                    className="flex justify-between gap-2"
                                >
                                    <span>{step.label}</span>
                                    <span className="text-muted-foreground">
                                        {formatDateTime(step.at)}
                                    </span>
                                </li>
                            ))}
                        </ol>
                        {order.notes && (
                            <p className="mt-4 text-sm text-muted-foreground">
                                {order.notes}
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

OrderShow.layout = {
    breadcrumbs: [{ title: 'Pedidos', href: index() }],
};
