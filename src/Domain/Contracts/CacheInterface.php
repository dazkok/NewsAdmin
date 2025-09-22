<?php

namespace App\Domain\Contracts;

interface CacheInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $ttl = 3600): bool;

    public function delete(string $key): bool;

    public function clear(): bool;

    public function has(string $key): bool;
}