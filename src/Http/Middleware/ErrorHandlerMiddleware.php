<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (\Throwable $e) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => [
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                ],
            ], JSON_THROW_ON_ERROR));

            $statusCode = match (true) {
                $e instanceof \DomainException => 400,
                $e instanceof \RuntimeException => 404,
                default => 500,
            };

            return $response
                ->withStatus($statusCode)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}

