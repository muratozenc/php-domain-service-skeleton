<?php

declare(strict_types=1);

namespace App\Application\Order;

final class OrderDTO
{
    /**
     * @param OrderItemDTO[] $items
     */
    public function __construct(
        public readonly int $id,
        public readonly string $state,
        public readonly string $createdAt,
        public readonly ?string $updatedAt,
        public readonly array $items,
        public readonly int $totalCents
    ) {
    }
}

