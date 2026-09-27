<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Operator = 'operator';
    case Viewer = 'viewer';

    public function canManageOrders(): bool
    {
        return $this !== self::Viewer;
    }
}
