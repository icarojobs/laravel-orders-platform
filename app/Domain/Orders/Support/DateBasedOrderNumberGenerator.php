<?php

namespace App\Domain\Orders\Support;

use App\Domain\Orders\Contracts\OrderNumberGenerator;
use Illuminate\Support\Str;

class DateBasedOrderNumberGenerator implements OrderNumberGenerator
{
    public function next(): string
    {
        return sprintf('ORD-%s-%s', now()->format('ymd'), Str::upper(Str::random(6)));
    }
}
