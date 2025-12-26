<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Domain\Order\OrderRepositoryInterface;
use App\Infrastructure\Cache\CacheInterface;

final class ConfirmOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CacheInterface $cache
    ) {
    }

    public function handle(ConfirmOrderCommand $command): OrderDTO
    {
        $order = $this->orderRepository->findById($command->orderId);

        if ($order === null) {
            throw new \RuntimeException("Order {$command->orderId} not found");
        }

        $order->confirm();
        $this->orderRepository->save($order);

        $this->cache->delete("order:{$command->orderId}");

        return $this->toDTO($order);
    }

    private function toDTO(\App\Domain\Order\Order $order): OrderDTO
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

