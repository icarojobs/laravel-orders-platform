<?php

namespace App\Policies;

use App\Domain\Orders\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tokenCan('orders:read');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->tokenCan('orders:read');
    }

    public function create(User $user): bool
    {
        return $this->canWrite($user);
    }

    public function updateStatus(User $user, Order $order, OrderStatus $target): bool
    {
        if ($target === OrderStatus::Cancelled) {
            return $this->canWrite($user) && $user->hasRole(UserRole::Admin);
        }

        return $this->canWrite($user);
    }

    private function canWrite(User $user): bool
    {
        return $user->role->canManageOrders() && $user->tokenCan('orders:write');
    }
}
