<?php

declare(strict_types=1);

namespace App\Domain\Order;

enum OrderState: string
{
    case DRAFT = 'DRAFT';
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';
}

