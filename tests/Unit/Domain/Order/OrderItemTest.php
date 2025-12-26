<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Order;

use App\Domain\Order\OrderItem;
use PHPUnit\Framework\TestCase;

final class OrderItemTest extends TestCase
{
    public function testCreateValidOrderItem(): void
    {
        $item = OrderItem::create(1, 100, 'Product A', 2, 5000);

        $this->assertSame(1, $item->getId());
        $this->assertSame(100, $item->getOrderId());
        $this->assertSame('Product A', $item->getProductName());
        $this->assertSame(2, $item->getQuantity());
        $this->assertSame(5000, $item->getPriceCents());
        $this->assertSame(10000, $item->getTotalCents());
    }

    public function testCannotCreateItemWithZeroQuantity(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Order item quantity must be at least 1');

        OrderItem::create(1, 100, 'Product A', 0, 5000);
    }

    public function testCannotCreateItemWithNegativeQuantity(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Order item quantity must be at least 1');

        OrderItem::create(1, 100, 'Product A', -1, 5000);
    }

    public function testCannotCreateItemWithNegativePrice(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Order item price must be non-negative');

        OrderItem::create(1, 100, 'Product A', 2, -100);
    }

    public function testCanCreateItemWithZeroPrice(): void
    {
        $item = OrderItem::create(1, 100, 'Free Product', 1, 0);

        $this->assertSame(0, $item->getPriceCents());
        $this->assertSame(0, $item->getTotalCents());
    }
}

