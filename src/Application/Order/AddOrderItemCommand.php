<?php

declare(strict_types=1);

namespace App\Application\Order;

final class AddOrderItemCommand
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $productName,
        public readonly int $quantity,
        public readonly int $priceCents
    ) {
    }
}

