<?php

namespace App\Domain\Orders\Contracts;

interface OrderNumberGenerator
{
    public function next(): string;
}
