<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Domain\Order\OrderRepositoryInterface;
use App\Infrastructure\Cache\CacheInterface;

final class CancelOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CacheInterface $cache
    ) {
    }

    public function handle(CancelOrderCommand $command): OrderDTO
    {
        $order = $this->orderRepository->findById($command->orderId);

        if ($order === null) {
            throw new \RuntimeException("Order {$command->orderId} not found");
        }

        $audit = $order->cancel($command->reason);
        $this->orderRepository->save($order);
        $this->orderRepository->saveAudit($audit);

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

