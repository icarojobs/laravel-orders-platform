<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Estoque - {{ config('app.name') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-neutral-50 font-sans text-neutral-900 antialiased dark:bg-neutral-950 dark:text-neutral-100">
        <header class="border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <nav class="mx-auto flex max-w-5xl items-center gap-6 px-4 py-3 text-sm">
                <span class="font-semibold">{{ config('app.name') }}</span>
                <a href="{{ route('dashboard') }}" class="text-neutral-600 hover:text-neutral-900 dark:text-neutral-400">Dashboard</a>
                <a href="{{ route('orders.index') }}" class="text-neutral-600 hover:text-neutral-900 dark:text-neutral-400">Pedidos</a>
                <span class="font-medium">Estoque</span>
            </nav>
        </header>
        <main class="mx-auto max-w-5xl px-4 py-6">
            <livewire:low-stock-products />
        </main>
    </body>
</html>
