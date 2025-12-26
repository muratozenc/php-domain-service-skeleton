<?php

declare(strict_types=1);

namespace App\Application\Order;

final class ConfirmOrderCommand
{
    public function __construct(
        public readonly int $orderId
    ) {
    }
}

