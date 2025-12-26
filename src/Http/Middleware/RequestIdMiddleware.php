<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestIdMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $requestId = $request->getHeaderLine('X-Request-Id');

        if (empty($requestId)) {
            $requestId = $this->generateRequestId();
        }

        $request = $request->withAttribute('request_id', $requestId);

        $response = $handler->handle($request);

        return $response->withHeader('X-Request-Id', $requestId);
    }

    private function generateRequestId(): string
    {
        return bin2hex(random_bytes(16));
    }
}

