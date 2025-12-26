<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Order\AddOrderItemCommand;
use App\Application\Order\AddOrderItemHandler;
use App\Application\Order\CancelOrderCommand;
use App\Application\Order\CancelOrderHandler;
use App\Application\Order\ConfirmOrderCommand;
use App\Application\Order\ConfirmOrderHandler;
use App\Application\Order\CreateOrderCommand;
use App\Application\Order\CreateOrderHandler;
use App\Application\Order\GetOrderHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

final class OrderController
{
    public function __construct(
        private readonly CreateOrderHandler $createOrderHandler,
        private readonly AddOrderItemHandler $addOrderItemHandler,
        private readonly ConfirmOrderHandler $confirmOrderHandler,
        private readonly CancelOrderHandler $cancelOrderHandler,
        private readonly GetOrderHandler $getOrderHandler
    ) {
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $dto = $this->createOrderHandler->handle(new CreateOrderCommand());

        $response->getBody()->write(json_encode([
            'id' => $dto->id,
            'state' => $dto->state,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
            'items' => array_map(fn($item) => [
                'id' => $item->id,
                'product_name' => $item->productName,
                'quantity' => $item->quantity,
                'price_cents' => $item->priceCents,
                'total_cents' => $item->totalCents,
            ], $dto->items),
            'total_cents' => $dto->totalCents,
        ], JSON_THROW_ON_ERROR));
        return $response
            ->withStatus(201)
            ->withHeader('Content-Type', 'application/json');
    }

    public function addItem(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $orderId = (int) $args['id'];
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $command = new AddOrderItemCommand(
            $orderId,
            $body['product_name'] ?? '',
            (int) ($body['quantity'] ?? 0),
            (int) ($body['price_cents'] ?? 0)
        );

        $dto = $this->addOrderItemHandler->handle($command);

        $response->getBody()->write(json_encode([
            'id' => $dto->id,
            'state' => $dto->state,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
            'items' => array_map(fn($item) => [
                'id' => $item->id,
                'product_name' => $item->productName,
                'quantity' => $item->quantity,
                'price_cents' => $item->priceCents,
                'total_cents' => $item->totalCents,
            ], $dto->items),
            'total_cents' => $dto->totalCents,
        ], JSON_THROW_ON_ERROR));
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
    }

    public function confirm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $orderId = (int) $args['id'];

        $command = new ConfirmOrderCommand($orderId);
        $dto = $this->confirmOrderHandler->handle($command);

        $response->getBody()->write(json_encode([
            'id' => $dto->id,
            'state' => $dto->state,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
            'items' => array_map(fn($item) => [
                'id' => $item->id,
                'product_name' => $item->productName,
                'quantity' => $item->quantity,
                'price_cents' => $item->priceCents,
                'total_cents' => $item->totalCents,
            ], $dto->items),
            'total_cents' => $dto->totalCents,
        ], JSON_THROW_ON_ERROR));
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
    }

    public function cancel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $orderId = (int) $args['id'];
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $command = new CancelOrderCommand(
            $orderId,
            $body['reason'] ?? 'No reason provided'
        );

        $dto = $this->cancelOrderHandler->handle($command);

        $response->getBody()->write(json_encode([
            'id' => $dto->id,
            'state' => $dto->state,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
            'items' => array_map(fn($item) => [
                'id' => $item->id,
                'product_name' => $item->productName,
                'quantity' => $item->quantity,
                'price_cents' => $item->priceCents,
                'total_cents' => $item->totalCents,
            ], $dto->items),
            'total_cents' => $dto->totalCents,
        ], JSON_THROW_ON_ERROR));
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
    }

    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $orderId = (int) $args['id'];

        $dto = $this->getOrderHandler->handle($orderId);

        $response->getBody()->write(json_encode([
            'id' => $dto->id,
            'state' => $dto->state,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
            'items' => array_map(fn($item) => [
                'id' => $item->id,
                'product_name' => $item->productName,
                'quantity' => $item->quantity,
                'price_cents' => $item->priceCents,
                'total_cents' => $item->totalCents,
            ], $dto->items),
            'total_cents' => $dto->totalCents,
        ], JSON_THROW_ON_ERROR));
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
    }
}

