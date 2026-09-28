export type SalesReport = {
    period: { from: string; to: string };
    summary: {
        orders: number;
        revenue_cents: number;
        average_ticket_cents: number;
    };
    by_status: Record<string, number>;
    by_day: { day: string; orders: number; revenue_cents: number }[];
    top_products: {
        sku: string;
        name: string;
        units: number;
        revenue_cents: number;
    }[];
};
