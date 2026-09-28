export type OrderStatus =
    | 'pending'
    | 'paid'
    | 'shipped'
    | 'delivered'
    | 'cancelled';

export type Option<T extends string = string> = {
    value: T;
    label: string;
};

export type Customer = {
    id: number;
    name: string;
    email: string;
    city: string | null;
};

export type OrderItem = {
    id: number;
    product_id: number;
    sku: string;
    product: string;
    quantity: number;
    unit_price_cents: number;
    total_cents: number;
};

export type Order = {
    id: number;
    number: string;
    status: OrderStatus;
    status_label: string;
    total_cents: number;
    notes: string | null;
    customer: Customer;
    items: OrderItem[];
    placed_at: string;
    paid_at: string | null;
    shipped_at: string | null;
    delivered_at: string | null;
    cancelled_at: string | null;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        per_page: number;
    };
};
