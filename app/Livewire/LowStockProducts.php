<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

class LowStockProducts extends Component
{
    #[Url]
    #[Validate('integer|min:0|max:10000')]
    public int $threshold = 25;

    public ?string $notice = null;

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->where('stock', '<=', max(0, $this->threshold))
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    public function updatedThreshold(): void
    {
        $this->validateOnly('threshold');
        $this->notice = null;
    }

    public function restock(int $productId, int $quantity = 10): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->role->canManageOrders(), 403);

        $product = Product::query()->findOrFail($productId);
        $product->increment('stock', max(1, min($quantity, 1000)));

        $this->notice = "{$product->name}: estoque atualizado para {$product->stock}.";
        unset($this->products);
    }

    public function render(): View
    {
        return view('livewire.low-stock-products');
    }
}
