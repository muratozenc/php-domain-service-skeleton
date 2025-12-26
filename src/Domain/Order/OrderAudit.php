<?php

declare(strict_types=1);

namespace App\Domain\Order;

final class OrderAudit
{
    private function __construct(
        private readonly int $id,
        private readonly int $orderId,
        private readonly string $action,
        private readonly string $reason,
        private readonly \DateTimeImmutable $createdAt
    ) {
    }

    public static function create(
        int $id,
        int $orderId,
        string $action,
        string $reason,
        \DateTimeImmutable $createdAt
    ): self {
        return new self($id, $orderId, $action, $reason, $createdAt);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

