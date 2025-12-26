<?php

declare(strict_types=1);

namespace App\Application\Order;

final class OrderItemDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $productName,
        public readonly int $quantity,
        public readonly int $priceCents,
        public readonly int $totalCents
    ) {
    }
}

