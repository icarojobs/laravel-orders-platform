import { cn } from '@/lib/utils';
import type { OrderStatus } from '@/types';

const styles: Record<OrderStatus, string> = {
    pending:
        'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    paid: 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
    shipped:
        'bg-violet-100 text-violet-800 dark:bg-violet-500/15 dark:text-violet-300',
    delivered:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    cancelled:
        'bg-neutral-200 text-neutral-700 dark:bg-neutral-500/20 dark:text-neutral-300',
};

export function OrderStatusBadge({
    status,
    label,
}: {
    status: OrderStatus;
    label: string;
}) {
    return (
        <span
            data-testid="order-status"
            className={cn(
                'inline-flex rounded-md px-2 py-0.5 text-xs font-medium',
                styles[status],
            )}
        >
            {label}
        </span>
    );
}
