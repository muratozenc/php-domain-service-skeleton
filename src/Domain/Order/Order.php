<?php

declare(strict_types=1);

namespace App\Domain\Order;

final class Order
{
    /** @var OrderItem[] */
    private array $items = [];

    private function __construct(
        private readonly int $id,
        private OrderState $state,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null
    ) {
    }

    public static function createDraft(int $id, \DateTimeImmutable $createdAt): self
    {
        return new self($id, OrderState::DRAFT, $createdAt);
    }

    public function addItem(OrderItem $item): void
    {
        if ($this->state !== OrderState::DRAFT) {
            throw new \DomainException('Can only add items to DRAFT orders');
        }

        if ($item->getOrderId() !== $this->id) {
            throw new \DomainException('Order item does not belong to this order');
        }

        $this->items[] = $item;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function confirm(): void
    {
        if ($this->state !== OrderState::DRAFT) {
            throw new \DomainException('Can only confirm DRAFT orders');
        }

        if (count($this->items) === 0) {
            throw new \DomainException('Cannot confirm order without items');
        }

        $this->state = OrderState::CONFIRMED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function cancel(string $reason): OrderAudit
    {
        if ($this->state !== OrderState::CONFIRMED) {
            throw new \DomainException('Can only cancel CONFIRMED orders');
        }

        $this->state = OrderState::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();

        return OrderAudit::create(
            0,
            $this->id,
            'CANCELLED',
            $reason,
            new \DateTimeImmutable()
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getState(): OrderState
    {
        return $this->state;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return OrderItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotalCents(): int
    {
        return array_sum(array_map(fn(OrderItem $item) => $item->getTotalCents(), $this->items));
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}

