<?php

declare(strict_types=1);

namespace App\Domain\Order;

final class OrderItem
{
    private function __construct(
        private readonly int $id,
        private readonly int $orderId,
        private readonly string $productName,
        private readonly int $quantity,
        private readonly int $priceCents
    ) {
        $this->validate();
    }

    public static function create(
        int $id,
        int $orderId,
        string $productName,
        int $quantity,
        int $priceCents
    ): self {
        return new self($id, $orderId, $productName, $quantity, $priceCents);
    }

    private function validate(): void
    {
        if ($this->quantity < 1) {
            throw new \DomainException('Order item quantity must be at least 1');
        }

        if ($this->priceCents < 0) {
            throw new \DomainException('Order item price must be non-negative');
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    public function getTotalCents(): int
    {
        return $this->quantity * $this->priceCents;
    }
}

