<?php

namespace App\Domain\Orders\Exceptions;

use App\Domain\Orders\Enums\OrderStatus;
use DomainException;

class InvalidOrderTransition extends DomainException
{
    public static function between(OrderStatus $from, OrderStatus $to): self
    {
        return new self("Order cannot move from [{$from->value}] to [{$to->value}].");
    }
}
