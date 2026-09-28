import { Head, Link } from '@inertiajs/react';
import { formatMoney } from '@/lib/format';
import { dashboard, inventory } from '@/routes';
import { index as orders } from '@/routes/orders';
import type { SalesReport } from '@/types';

type Props = {
    report: SalesReport;
    statusLabels: Record<string, string>;
};

const shortDate = new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    timeZone: 'UTC',
});

function Kpi({
    label,
    value,
    testId,
}: {
    label: string;
    value: string;
    testId: string;
}) {
    return (
        <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p
                className="mt-1 text-2xl font-semibold tabular-nums"
                data-testid={testId}
            >
                {value}
            </p>
        </div>
    );
}

export default function Dashboard({ report, statusLabels }: Props) {
    const maxRevenue = Math.max(
        1,
        ...report.by_day.map((day) => day.revenue_cents),
    );

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-baseline justify-between gap-2">
                    <h1 className="text-xl font-semibold">Últimos 30 dias</h1>
                    <div className="flex gap-4 text-sm">
                        <Link
                            href={orders()}
                            className="underline-offset-4 hover:underline"
                        >
                            Ver pedidos
                        </Link>
                        <a
                            href={inventory.url()}
                            className="underline-offset-4 hover:underline"
                        >
                            Estoque baixo
                        </a>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Kpi
                        label="Faturamento"
                        value={formatMoney(report.summary.revenue_cents)}
                        testId="kpi-revenue"
                    />
                    <Kpi
                        label="Pedidos"
                        value={report.summary.orders.toLocaleString('pt-BR')}
                        testId="kpi-orders"
                    />
                    <Kpi
                        label="Ticket médio"
                        value={formatMoney(report.summary.average_ticket_cents)}
                        testId="kpi-ticket"
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <section className="rounded-xl border border-sidebar-border/70 p-4 lg:col-span-2 dark:border-sidebar-border">
                        <h2 className="mb-4 text-sm font-semibold">
                            Faturamento por dia
                        </h2>
                        <div className="flex h-48 items-end gap-1">
                            {report.by_day.map((day) => (
                                <div
                                    key={day.day}
                                    className="flex-1 rounded-t bg-primary/80 hover:bg-primary"
                                    style={{
                                        height: `${(day.revenue_cents / maxRevenue) * 100}%`,
                                    }}
                                    title={`${shortDate.format(new Date(day.day))}: ${formatMoney(day.revenue_cents)} (${day.orders} pedidos)`}
                                />
                            ))}
                        </div>
                        {report.by_day.length > 0 && (
                            <div className="mt-2 flex justify-between text-xs text-muted-foreground">
                                <span>
                                    {shortDate.format(
                                        new Date(report.by_day[0].day),
                                    )}
                                </span>
                                <span>
                                    {shortDate.format(
                                        new Date(
                                            report.by_day[
                                                report.by_day.length - 1
                                            ].day,
                                        ),
                                    )}
                                </span>
                            </div>
                        )}
                    </section>

                    <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h2 className="mb-4 text-sm font-semibold">
                            Pedidos por status
                        </h2>
                        <ul className="space-y-2 text-sm">
                            {Object.entries(report.by_status).map(
                                ([status, count]) => (
                                    <li
                                        key={status}
                                        className="flex justify-between"
                                    >
                                        <span>
                                            {statusLabels[status] ?? status}
                                        </span>
                                        <span className="tabular-nums">
                                            {count.toLocaleString('pt-BR')}
                                        </span>
                                    </li>
                                ),
                            )}
                        </ul>
                    </section>
                </div>

                <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h2 className="mb-4 text-sm font-semibold">
                        Produtos mais vendidos
                    </h2>
                    <table className="w-full text-sm">
                        <thead className="text-left text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="py-2">SKU</th>
                                <th className="py-2">Produto</th>
                                <th className="py-2 text-right">Unidades</th>
                                <th className="py-2 text-right">Receita</th>
                            </tr>
                        </thead>
                        <tbody>
                            {report.top_products.map((product) => (
                                <tr
                                    key={product.sku}
                                    className="border-t border-sidebar-border/70"
                                >
                                    <td className="py-2 font-mono text-xs">
                                        {product.sku}
                                    </td>
                                    <td className="py-2">{product.name}</td>
                                    <td className="py-2 text-right tabular-nums">
                                        {product.units.toLocaleString('pt-BR')}
                                    </td>
                                    <td className="py-2 text-right tabular-nums">
                                        {formatMoney(product.revenue_cents)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
