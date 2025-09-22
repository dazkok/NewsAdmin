<?php

namespace App\Infrastructure\Cache;

use App\Domain\Contracts\CacheInterface;
use Redis;
use RedisException;

class RedisCache implements CacheInterface
{
    private Redis $redis;
    private string $prefix;

    public function __construct(string $host = 'redis', int $port = 6379, string $prefix = 'news_')
    {
        $this->redis = new Redis();
        $this->prefix = $prefix;

        try {
            $this->redis->connect($host, $port);
        } catch (RedisException $e) {
            throw new \RuntimeException('Redis connection failed: ' . $e->getMessage());
        }
    }

    public function get(string $key): mixed
    {
        try {
            $data = $this->redis->get($this->prefix . $key);
            return $data ? unserialize($data) : null;
        } catch (RedisException $e) {
            error_log('Redis get error: ' . $e->getMessage());
            return null;
        }
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        try {
            return $this->redis->setex($this->prefix . $key, $ttl, serialize($value));
        } catch (RedisException $e) {
            error_log('Redis set error: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            return $this->redis->del($this->prefix . $key) > 0;
        } catch (RedisException $e) {
            error_log('Redis delete error: ' . $e->getMessage());
            return false;
        }
    }

    public function clear(): bool
    {
        try {
            $keys = $this->redis->keys($this->prefix . '*');
            if (!empty($keys)) {
                return $this->redis->del($keys) > 0;
            }
            return true;
        } catch (RedisException $e) {
            error_log('Redis clear error: ' . $e->getMessage());
            return false;
        }
    }

    public function has(string $key): bool
    {
        try {
            return $this->redis->exists($this->prefix . $key) > 0;
        } catch (RedisException $e) {
            error_log('Redis exists error: ' . $e->getMessage());
            return false;
        }
    }

    public function __destruct()
    {
        if ($this->redis) {
            $this->redis->close();
        }
    }
}