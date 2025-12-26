<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Order;

use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\OrderState;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testCreateDraftOrder(): void
    {
        $order = Order::createDraft(1, new \DateTimeImmutable());

        $this->assertSame(1, $order->getId());
        $this->assertSame(OrderState::DRAFT, $order->getState());
        $this->assertEmpty($order->getItems());
    }

    public function testCannotConfirmOrderWithoutItems(): void
    {
        $order = Order::createDraft(1, new \DateTimeImmutable());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot confirm order without items');

        $order->confirm();
    }

    public function testCanConfirmOrderWithItems(): void
    {
        $order = Order::createDraft(1, new \DateTimeImmutable());
        $item = OrderItem::create(1, 1, 'Product A', 2, 5000);
        $order->addItem($item);

        $order->confirm();

        $this->assertSame(OrderState::CONFIRMED, $order->getState());
    }

    public function testCannotAddItemToNonDraftOrder(): void
    {
        $order = Order::createDraft(1, new \DateTimeImmutable());
        $item = OrderItem::create(1, 1, 'Product A', 2, 5000);
        $order->addItem($item);
        $order->confirm();

        $item2 = OrderItem::create(2, 1, 'Product B', 1, 3000);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Can only add items to DRAFT orders');

        $order->addItem($item2);
    }

    public function testCannotCancelNonConfirmedOrder(): void
    {
        $order = Order::createDraft(1, new \DateTimeImmutable());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Can only cancel CONFIRMED orders');

        $order->cancel('Test reason');
    }

    public function testCancelConfirmedOrderCreatesAudit(): void
    {
        $order = Order::createDraft(1, new \DateTimeImmutable());
        $item = OrderItem::create(1, 1, 'Product A', 2, 5000);
        $order->addItem($item);
        $order->confirm();

        $audit = $order->cancel('Customer requested cancellation');

        $this->assertSame(OrderState::CANCELLED, $order->getState());
        $this->assertSame(1, $audit->getOrderId());
        $this->assertSame('CANCELLED', $audit->getAction());
        $this->assertSame('Customer requested cancellation', $audit->getReason());
    }

    public function testOrderTotalCalculation(): void
    {
        $order = Order::createDraft(1, new \DateTimeImmutable());
        $item1 = OrderItem::create(1, 1, 'Product A', 2, 5000);
        $item2 = OrderItem::create(2, 1, 'Product B', 1, 10000);
        $order->addItem($item1);
        $order->addItem($item2);

        $this->assertSame(20000, $order->getTotalCents());
    }
}

