<?php

declare(strict_types=1);

namespace NotificationService\Handlers;

final class Money
{
    public static function brl(int $cents): string
    {
        return 'R$ '.number_format($cents / 100, 2, ',', '.');
    }
}
