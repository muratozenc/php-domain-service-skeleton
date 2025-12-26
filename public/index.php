<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Http\Controller\OrderController;
use App\Http\Middleware\ErrorHandlerMiddleware;
use App\Http\Middleware\RequestIdMiddleware;
use App\Infrastructure\Cache\RedisCache;
use App\Infrastructure\Database\Database;
use App\Infrastructure\Order\OrderRepository;
use App\Application\Order\CreateOrderHandler;
use App\Application\Order\AddOrderItemHandler;
use App\Application\Order\ConfirmOrderHandler;
use App\Application\Order\CancelOrderHandler;
use App\Application\Order\GetOrderHandler;
use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Predis\Client;
use Slim\Factory\AppFactory;

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

$database = new Database(
    $_ENV['DB_HOST'] ?? 'mysql',
    (int) ($_ENV['DB_PORT'] ?? 3306),
    $_ENV['DB_NAME'] ?? 'orders_db',
    $_ENV['DB_USER'] ?? 'orders_user',
    $_ENV['DB_PASS'] ?? 'orders_pass'
);

$redis = new Client([
    'host' => $_ENV['REDIS_HOST'] ?? 'redis',
    'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
]);

$cache = new RedisCache($redis);
$orderRepository = new OrderRepository($database);

$createOrderHandler = new CreateOrderHandler($orderRepository);
$addOrderItemHandler = new AddOrderItemHandler($orderRepository, $cache);
$confirmOrderHandler = new ConfirmOrderHandler($orderRepository, $cache);
$cancelOrderHandler = new CancelOrderHandler($orderRepository, $cache);
$getOrderHandler = new GetOrderHandler($orderRepository, $cache);

$orderController = new OrderController(
    $createOrderHandler,
    $addOrderItemHandler,
    $confirmOrderHandler,
    $cancelOrderHandler,
    $getOrderHandler
);

$app = AppFactory::create();

$app->add(new ErrorHandlerMiddleware());
$app->add(new RequestIdMiddleware());

$app->post('/orders', [$orderController, 'create']);
$app->post('/orders/{id}/items', [$orderController, 'addItem']);
$app->post('/orders/{id}/confirm', [$orderController, 'confirm']);
$app->post('/orders/{id}/cancel', [$orderController, 'cancel']);
$app->get('/orders/{id}', [$orderController, 'get']);

$app->run();

