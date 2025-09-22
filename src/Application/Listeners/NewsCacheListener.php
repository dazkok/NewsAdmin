<?php

namespace App\Application\Listeners;

use App\Domain\Contracts\CacheInterface;

class NewsCacheListener
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function handleNewsCreated($news): void
    {
        $this->invalidateNewsCache();
    }

    public function handleNewsUpdated($news): void
    {
        $this->invalidateNewsCache();
    }

    public function handleNewsDeleted($news): void
    {
        $this->invalidateNewsCache();
    }

    private function invalidateNewsCache(): void
    {
        $this->cache->delete('news_all');
        $this->cache->delete('news_api_all');
        $this->cache->delete('news_page_all');

        $keys = $this->cache->get('news_keys') ?: [];
        foreach ($keys as $key) {
            $this->cache->delete($key);
        }
    }
}