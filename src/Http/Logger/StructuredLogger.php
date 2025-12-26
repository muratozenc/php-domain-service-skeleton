<?php

declare(strict_types=1);

namespace App\Http\Logger;

use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class StructuredLogger
{
    public function __construct(
        private readonly Logger $logger
    ) {
    }

    public function logRequest(ServerRequestInterface $request, ResponseInterface $response): void
    {
        $requestId = $request->getAttribute('request_id', 'unknown');

        $this->logger->info('HTTP Request', [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'status' => $response->getStatusCode(),
        ]);
    }
}

