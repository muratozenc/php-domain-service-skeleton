<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Domain\Order\OrderRepositoryInterface;
use App\Infrastructure\Cache\CacheInterface;

final class GetOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CacheInterface $cache
    ) {
    }

    public function handle(int $orderId): OrderDTO
    {
        $cached = $this->cache->get("order:{$orderId}");
        if ($cached !== null) {
            return $this->deserializeDTO($cached);
        }

        $order = $this->orderRepository->findById($orderId);

        if ($order === null) {
            throw new \RuntimeException("Order {$orderId} not found");
        }

        $dto = $this->toDTO($order);

        $this->cache->set("order:{$orderId}", $this->serializeDTO($dto), 60);

        return $dto;
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

    private function serializeDTO(OrderDTO $dto): string
    {
        return json_encode([
            'id' => $dto->id,
            'state' => $dto->state,
            'createdAt' => $dto->createdAt,
            'updatedAt' => $dto->updatedAt,
            'items' => array_map(fn($item) => [
                'id' => $item->id,
                'productName' => $item->productName,
                'quantity' => $item->quantity,
                'priceCents' => $item->priceCents,
                'totalCents' => $item->totalCents,
            ], $dto->items),
            'totalCents' => $dto->totalCents,
        ], JSON_THROW_ON_ERROR);
    }

    private function deserializeDTO(string $json): OrderDTO
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $items = array_map(
            fn($item) => new OrderItemDTO(
                $item['id'],
                $item['productName'],
                $item['quantity'],
                $item['priceCents'],
                $item['totalCents']
            ),
            $data['items']
        );

        return new OrderDTO(
            $data['id'],
            $data['state'],
            $data['createdAt'],
            $data['updatedAt'] ?? null,
            $items,
            $data['totalCents']
        );
    }
}

