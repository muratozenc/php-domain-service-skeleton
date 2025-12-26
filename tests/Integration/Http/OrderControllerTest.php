<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Application\Order\AddOrderItemCommand;
use App\Application\Order\AddOrderItemHandler;
use App\Application\Order\CancelOrderCommand;
use App\Application\Order\CancelOrderHandler;
use App\Application\Order\ConfirmOrderCommand;
use App\Application\Order\ConfirmOrderHandler;
use App\Application\Order\CreateOrderCommand;
use App\Application\Order\CreateOrderHandler;
use App\Application\Order\GetOrderHandler;
use App\Http\Controller\OrderController;
use App\Infrastructure\Cache\RedisCache;
use App\Infrastructure\Database\Database;
use App\Infrastructure\Order\OrderRepository;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;

final class OrderControllerTest extends TestCase
{
    private Database $database;
    private OrderRepository $repository;
    private Client $redis;
    private OrderController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = new Database(
            $_ENV['DB_HOST'] ?? 'mysql',
            (int) ($_ENV['DB_PORT'] ?? 3306),
            $_ENV['DB_NAME'] ?? 'orders_db',
            $_ENV['DB_USER'] ?? 'orders_user',
            $_ENV['DB_PASS'] ?? 'orders_pass'
        );

        $this->repository = new OrderRepository($this->database);

        $this->redis = new Client([
            'host' => $_ENV['REDIS_HOST'] ?? 'redis',
            'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
        ]);

        $cache = new RedisCache($this->redis);

        $createHandler = new CreateOrderHandler($this->repository);
        $addItemHandler = new AddOrderItemHandler($this->repository, $cache);
        $confirmHandler = new ConfirmOrderHandler($this->repository, $cache);
        $cancelHandler = new CancelOrderHandler($this->repository, $cache);
        $getHandler = new GetOrderHandler($this->repository, $cache);

        $this->controller = new OrderController(
            $createHandler,
            $addItemHandler,
            $confirmHandler,
            $cancelHandler,
            $getHandler
        );

        $this->resetDatabase();
        $this->resetRedis();
    }

    private function resetDatabase(): void
    {
        $pdo = $this->database->getConnection();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('TRUNCATE TABLE order_items');
        $pdo->exec('TRUNCATE TABLE order_audit');
        $pdo->exec('TRUNCATE TABLE orders');
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function resetRedis(): void
    {
        $this->redis->flushdb();
    }

    public function testCreateOrder(): void
    {
        $request = $this->createRequest('POST', '/orders');
        $response = $this->controller->create($request, new \Slim\Psr7\Response());

        $this->assertSame(201, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('id', $body);
        $this->assertSame('DRAFT', $body['state']);
    }

    public function testAddItemToOrder(): void
    {
        $createRequest = $this->createRequest('POST', '/orders');
        $createResponse = $this->controller->create($createRequest, new \Slim\Psr7\Response());
        $orderId = json_decode((string) $createResponse->getBody(), true)['id'];

        $request = $this->createRequest('POST', "/orders/{$orderId}/items", [
            'product_name' => 'Test Product',
            'quantity' => 2,
            'price_cents' => 5000,
        ]);
        $response = $this->controller->addItem($request, new \Slim\Psr7\Response(), ['id' => (string) $orderId]);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertCount(1, $body['items']);
        $this->assertSame(10000, $body['total_cents']);
    }

    public function testConfirmOrder(): void
    {
        $createRequest = $this->createRequest('POST', '/orders');
        $createResponse = $this->controller->create($createRequest, new \Slim\Psr7\Response());
        $orderId = json_decode((string) $createResponse->getBody(), true)['id'];

        $addItemRequest = $this->createRequest('POST', "/orders/{$orderId}/items", [
            'product_name' => 'Test Product',
            'quantity' => 1,
            'price_cents' => 5000,
        ]);
        $this->controller->addItem($addItemRequest, new \Slim\Psr7\Response(), ['id' => (string) $orderId]);

        $confirmRequest = $this->createRequest('POST', "/orders/{$orderId}/confirm");
        $response = $this->controller->confirm($confirmRequest, new \Slim\Psr7\Response(), ['id' => (string) $orderId]);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('CONFIRMED', $body['state']);
    }

    public function testCannotConfirmOrderWithoutItems(): void
    {
        $createRequest = $this->createRequest('POST', '/orders');
        $createResponse = $this->controller->create($createRequest, new \Slim\Psr7\Response());
        $orderId = json_decode((string) $createResponse->getBody(), true)['id'];

        $order = $this->repository->findById($orderId);
        $this->assertNotNull($order);
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot confirm order without items');
        $order->confirm();
    }

    public function testCancelOrder(): void
    {
        $createRequest = $this->createRequest('POST', '/orders');
        $createResponse = $this->controller->create($createRequest, new \Slim\Psr7\Response());
        $orderId = json_decode((string) $createResponse->getBody(), true)['id'];

        $addItemRequest = $this->createRequest('POST', "/orders/{$orderId}/items", [
            'product_name' => 'Test Product',
            'quantity' => 1,
            'price_cents' => 5000,
        ]);
        $this->controller->addItem($addItemRequest, new \Slim\Psr7\Response(), ['id' => (string) $orderId]);

        $confirmRequest = $this->createRequest('POST', "/orders/{$orderId}/confirm");
        $this->controller->confirm($confirmRequest, new \Slim\Psr7\Response(), ['id' => (string) $orderId]);

        $cancelRequest = $this->createRequest('POST', "/orders/{$orderId}/cancel", [
            'reason' => 'Test cancellation',
        ]);
        $response = $this->controller->cancel($cancelRequest, new \Slim\Psr7\Response(), ['id' => (string) $orderId]);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('CANCELLED', $body['state']);

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare('SELECT * FROM order_audit WHERE order_id = ?');
        $stmt->execute([$orderId]);
        $audit = $stmt->fetch();

        $this->assertNotFalse($audit);
        $this->assertSame('CANCELLED', $audit['action']);
        $this->assertSame('Test cancellation', $audit['reason']);
    }

    public function testGetOrder(): void
    {
        $createRequest = $this->createRequest('POST', '/orders');
        $createResponse = $this->controller->create($createRequest, new \Slim\Psr7\Response());
        $orderId = json_decode((string) $createResponse->getBody(), true)['id'];

        $request = $this->createRequest('GET', "/orders/{$orderId}");
        $response = $this->controller->get($request, new \Slim\Psr7\Response(), ['id' => (string) $orderId]);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame($orderId, $body['id']);
        $this->assertSame('DRAFT', $body['state']);
    }

    private function createRequest(string $method, string $path, array $body = []): ServerRequestInterface
    {
        $uriFactory = new UriFactory();
        $streamFactory = new StreamFactory();
        $requestFactory = new ServerRequestFactory();

        $uri = $uriFactory->createUri($path);
        $request = $requestFactory->createServerRequest($method, $uri);

        if (!empty($body)) {
            $stream = $streamFactory->createStream(json_encode($body, JSON_THROW_ON_ERROR));
            $request = $request->withBody($stream)
                ->withHeader('Content-Type', 'application/json');
        }

        return $request;
    }
}
