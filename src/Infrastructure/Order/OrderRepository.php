<?php

declare(strict_types=1);

namespace App\Infrastructure\Order;

use App\Domain\Order\Order;
use App\Domain\Order\OrderAudit;
use App\Domain\Order\OrderItem;
use App\Domain\Order\OrderRepositoryInterface;
use App\Domain\Order\OrderState;
use App\Infrastructure\Database\Database;

final class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly Database $database
    ) {
    }

    public function save(Order $order): void
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare('SELECT id FROM orders WHERE id = ?');
        $stmt->execute([$order->getId()]);
        $exists = $stmt->fetch() !== false;

        if ($exists) {
            $stmt = $pdo->prepare(
                'UPDATE orders SET state = ?, updated_at = ? WHERE id = ?'
            );
            $stmt->execute([
                $order->getState()->value,
                $order->getUpdatedAt()?->format('Y-m-d H:i:s'),
                $order->getId(),
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO orders (id, state, created_at, updated_at) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $order->getId(),
                $order->getState()->value,
                $order->getCreatedAt()->format('Y-m-d H:i:s'),
                $order->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ]);
        }

        $stmt = $pdo->prepare('DELETE FROM order_items WHERE order_id = ?');
        $stmt->execute([$order->getId()]);

        foreach ($order->getItems() as $item) {
            $stmt = $pdo->prepare(
                'INSERT INTO order_items (id, order_id, product_name, quantity, price_cents) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $item->getId(),
                $item->getOrderId(),
                $item->getProductName(),
                $item->getQuantity(),
                $item->getPriceCents(),
            ]);
        }
    }

    public function findById(int $id): ?Order
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $order = Order::createDraft(
            (int) $row['id'],
            new \DateTimeImmutable($row['created_at'])
        );

        $reflection = new \ReflectionClass($order);
        $stateProperty = $reflection->getProperty('state');
        $stateProperty->setAccessible(true);
        $stateProperty->setValue($order, OrderState::from($row['state']));

        if ($row['updated_at'] !== null) {
            $updatedAtProperty = $reflection->getProperty('updatedAt');
            $updatedAtProperty->setAccessible(true);
            $updatedAtProperty->setValue($order, new \DateTimeImmutable($row['updated_at']));
        }

        $stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
        $stmt->execute([$id]);
        $itemRows = $stmt->fetchAll();

        $items = array_map(
            fn($itemRow) => OrderItem::create(
                (int) $itemRow['id'],
                (int) $itemRow['order_id'],
                $itemRow['product_name'],
                (int) $itemRow['quantity'],
                (int) $itemRow['price_cents']
            ),
            $itemRows
        );

        $itemsProperty = $reflection->getProperty('items');
        $itemsProperty->setAccessible(true);
        $itemsProperty->setValue($order, $items);

        return $order;
    }

    public function saveAudit(OrderAudit $audit): void
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare(
            'INSERT INTO order_audit (order_id, action, reason, created_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $audit->getOrderId(),
            $audit->getAction(),
            $audit->getReason(),
            $audit->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
}

