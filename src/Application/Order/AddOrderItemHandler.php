<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\OrderRepositoryInterface;
use App\Infrastructure\Cache\CacheInterface;

final class AddOrderItemHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CacheInterface $cache
    ) {
    }

    public function handle(AddOrderItemCommand $command): OrderDTO
    {
        $order = $this->orderRepository->findById($command->orderId);

        if ($order === null) {
            throw new \RuntimeException("Order {$command->orderId} not found");
        }

        $item = OrderItem::create(
            $this->generateItemId(),
            $command->orderId,
            $command->productName,
            $command->quantity,
            $command->priceCents
        );

        $order->addItem($item);
        $this->orderRepository->save($order);

        $this->cache->delete("order:{$command->orderId}");

        return $this->toDTO($order);
    }

    private function generateItemId(): int
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

