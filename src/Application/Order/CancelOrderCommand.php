<?php

declare(strict_types=1);

namespace App\Application\Order;

final class CancelOrderCommand
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $reason
    ) {
    }
}

