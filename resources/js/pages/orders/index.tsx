import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { OrderStatusBadge } from '@/components/order-status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime, formatMoney } from '@/lib/format';
import { index, show } from '@/routes/orders';
import type { Option, Order, OrderStatus, Paginated } from '@/types';

type Filters = {
    status?: OrderStatus | '';
    search?: string;
    from?: string;
    to?: string;
};

type Props = {
    orders: Paginated<Order>;
    filters: Filters;
    statuses: Option<OrderStatus>[];
};

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none';

export default function OrdersIndex({ orders, filters, statuses }: Props) {
    const [form, setForm] = useState<Filters>({
        status: filters.status ?? '',
        search: filters.search ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
    });

    const apply = (event?: FormEvent) => {
        event?.preventDefault();
        const query = Object.fromEntries(
            Object.entries(form).filter(
                ([, value]) => value !== '' && value !== undefined,
            ),
        );
        router.get(index.url(), query, { preserveState: true, replace: true });
    };

    const clear = () => {
        setForm({ status: '', search: '', from: '', to: '' });
        router.get(index.url(), {}, { replace: true });
    };

    return (
        <>
            <Head title="Pedidos" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <form
                    onSubmit={apply}
                    className="grid gap-3 rounded-xl border border-sidebar-border/70 p-4 md:grid-cols-5 dark:border-sidebar-border"
                >
                    <div className="grid gap-1.5 md:col-span-2">
                        <Label htmlFor="search">Buscar</Label>
                        <Input
                            id="search"
                            placeholder="Número do pedido ou cliente"
                            value={form.search}
                            onChange={(e) =>
                                setForm({ ...form, search: e.target.value })
                            }
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="status">Status</Label>
                        <select
                            id="status"
                            className={selectClass}
                            value={form.status}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    status: e.target.value as OrderStatus | '',
                                })
                            }
                        >
                            <option value="">Todos</option>
                            {statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="from">De</Label>
                        <Input
                            id="from"
                            type="date"
                            value={form.from}
                            onChange={(e) =>
                                setForm({ ...form, from: e.target.value })
                            }
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="to">Até</Label>
                        <Input
                            id="to"
                            type="date"
                            value={form.to}
                            onChange={(e) =>
                                setForm({ ...form, to: e.target.value })
                            }
                        />
                    </div>
                    <div className="flex gap-2 md:col-span-5 md:justify-end">
                        <Button type="button" variant="ghost" onClick={clear}>
                            Limpar
                        </Button>
                        <Button type="submit">Filtrar</Button>
                    </div>
                </form>

                <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <table
                        className="w-full text-sm"
                        data-testid="orders-table"
                    >
                        <thead className="bg-muted/50 text-left text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="px-4 py-3">Pedido</th>
                                <th className="px-4 py-3">Cliente</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3 text-right">Itens</th>
                                <th className="px-4 py-3 text-right">Total</th>
                                <th className="px-4 py-3">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.data.map((order) => (
                                <tr
                                    key={order.id}
                                    className="border-t border-sidebar-border/70 hover:bg-muted/30"
                                >
                                    <td className="px-4 py-3 font-medium">
                                        <Link
                                            href={show(order.id)}
                                            className="hover:underline"
                                        >
                                            {order.number}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">
                                        {order.customer.name}
                                    </td>
                                    <td className="px-4 py-3">
                                        <OrderStatusBadge
                                            status={order.status}
                                            label={order.status_label}
                                        />
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {order.items.length}
                                    </td>
                                    <td className="px-4 py-3 text-right tabular-nums">
                                        {formatMoney(order.total_cents)}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {formatDateTime(order.placed_at)}
                                    </td>
                                </tr>
                            ))}
                            {orders.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-10 text-center text-muted-foreground"
                                    >
                                        Nenhum pedido encontrado.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span data-testid="orders-total">
                        {orders.meta.total} pedido(s) · página{' '}
                        {orders.meta.current_page} de {orders.meta.last_page}
                    </span>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!orders.links.prev}
                            asChild={!!orders.links.prev}
                        >
                            {orders.links.prev ? (
                                <Link href={orders.links.prev} preserveState>
                                    Anterior
                                </Link>
                            ) : (
                                <span>Anterior</span>
                            )}
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!orders.links.next}
                            asChild={!!orders.links.next}
                        >
                            {orders.links.next ? (
                                <Link href={orders.links.next} preserveState>
                                    Próxima
                                </Link>
                            ) : (
                                <span>Próxima</span>
                            )}
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}

OrdersIndex.layout = {
    breadcrumbs: [{ title: 'Pedidos', href: index() }],
};
