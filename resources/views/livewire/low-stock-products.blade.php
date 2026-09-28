<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Estoque baixo</h1>
            <p class="text-sm text-neutral-500">Produtos com estoque menor ou igual ao limite. Atualiza sem recarregar a página.</p>
        </div>
        <label class="grid gap-1.5 text-sm font-medium">
            Limite
            <input
                type="number"
                min="0"
                wire:model.live.debounce.300ms="threshold"
                data-testid="threshold"
                class="h-9 w-32 rounded-md border border-neutral-300 bg-transparent px-3 text-sm dark:border-neutral-700"
            >
        </label>
    </div>

    @error('threshold') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

    @if ($notice)
        <p class="rounded-md bg-emerald-50 px-3 py-2 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300" data-testid="restock-message">{{ $notice }}</p>
    @endif

    <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-800">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-left text-xs uppercase text-neutral-500 dark:bg-neutral-900">
                <tr>
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">Produto</th>
                    <th class="px-4 py-3 text-right">Estoque</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->products as $product)
                    <tr wire:key="product-{{ $product->id }}" class="border-t border-neutral-200 dark:border-neutral-800">
                        <td class="px-4 py-3 font-mono text-xs">{{ $product->sku }}</td>
                        <td class="px-4 py-3">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-right tabular-nums @if ($product->stock === 0) font-semibold text-red-600 @endif">{{ $product->stock }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('create', App\Models\Order::class)
                                <button
                                    type="button"
                                    wire:click="restock({{ $product->id }})"
                                    wire:loading.attr="disabled"
                                    class="rounded-md border border-neutral-300 px-3 py-1 text-xs font-medium hover:bg-neutral-100 disabled:opacity-50 dark:border-neutral-700 dark:hover:bg-neutral-800"
                                >+10 unidades</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-neutral-500">Nenhum produto abaixo do limite.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
