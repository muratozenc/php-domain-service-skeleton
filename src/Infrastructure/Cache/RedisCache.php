<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use Predis\Client;

final class RedisCache implements CacheInterface
{
    public function __construct(
        private readonly Client $redis
    ) {
    }

    public function get(string $key): ?string
    {
        $value = $this->redis->get($key);
        return $value !== null ? (string) $value : null;
    }

    public function set(string $key, string $value, int $ttlSeconds): void
    {
        $this->redis->setex($key, $ttlSeconds, $value);
    }

    public function delete(string $key): void
    {
        $this->redis->del($key);
    }
}

