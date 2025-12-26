<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Domain\Order\Order;
use App\Domain\Order\OrderRepositoryInterface;

final class CreateOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    public function handle(CreateOrderCommand $command): OrderDTO
    {
        $order = Order::createDraft(
            $this->generateOrderId(),
            new \DateTimeImmutable()
        );

        $this->orderRepository->save($order);

        return $this->toDTO($order);
    }

    private function generateOrderId(): int
    {
        return (int) (microtime(true) * 1000000);
    }

    private function toDTO(Order $order): OrderDTO
    {
        $items = array_map(
            fn($item) => new OrderItemDTO(
                $item->getId(),
                $item->getProductName(),
                $item->getQuantity(),
                $item->getPriceCents(),
                $item->getTotalCents()
            ),
            $order->getItems()
        );

        return new OrderDTO(
            $order->getId(),
            $order->getState()->value,
            $order->getCreatedAt()->format('Y-m-d H:i:s'),
            $order->getUpdatedAt()?->format('Y-m-d H:i:s'),
            $items,
            $order->getTotalCents()
        );
    }
}

