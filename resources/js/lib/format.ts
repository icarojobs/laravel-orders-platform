const currency = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const dateTime = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
});

export function formatMoney(cents: number): string {
    return currency.format(cents / 100);
}

export function formatDateTime(iso: string | null): string {
    return iso ? dateTime.format(new Date(iso)) : '—';
}
