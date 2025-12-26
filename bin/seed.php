#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Infrastructure\Database\Database;
use App\Infrastructure\Order\OrderRepository;
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

$database = new Database(
    $_ENV['DB_HOST'],
    (int) $_ENV['DB_PORT'],
    $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

$repository = new OrderRepository($database);

$order = Order::createDraft(1001, new \DateTimeImmutable());

$item1 = OrderItem::create(2001, 1001, 'Product A', 2, 5000);
$item2 = OrderItem::create(2002, 1001, 'Product B', 1, 10000);

$order->addItem($item1);
$order->addItem($item2);

$repository->save($order);

echo "Seeded sample order with ID: {$order->getId()}\n";

