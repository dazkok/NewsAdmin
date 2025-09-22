<?php

namespace App\Domain\Services;

use App\Domain\Contracts\CacheInterface;
use App\Domain\Contracts\EventDispatcherInterface;
use App\Domain\Contracts\LoggerInterface;
use App\Domain\Contracts\NewsRepositoryInterface;
use App\Domain\Events\NewsEvents;
use App\Domain\Models\News;

class NewsService
{
    private NewsRepositoryInterface $newsRepository;
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private EventDispatcherInterface $dispatcher;
    private int $cacheTtl;

    public function __construct(
        NewsRepositoryInterface  $newsRepository,
        CacheInterface           $cache,
        LoggerInterface          $logger,
        EventDispatcherInterface $dispatcher,
        int                      $cacheTtl = 3600
    )
    {
        $this->newsRepository = $newsRepository;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->cacheTtl = $cacheTtl;
    }

    public function getAllNews(): array
    {
        $cacheKey = 'news_all';

        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            $this->logger->debug('Cache hit for all news');
            return $cachedData;
        }

        $this->logger->debug('Cache miss for all news, querying database');
        $news = $this->newsRepository->all();
        $this->cache->set($cacheKey, $news, $this->cacheTtl);

        $this->logger->info('All news loaded from repository', ['count' => count($news)]);
        return $news;
    }

    public function getNewsById(int $id): News
    {
        $cacheKey = "news_{$id}";

        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            $this->logger->debug("Cache hit for news ID: {$id}");
            return $cachedData;
        }

        $this->logger->debug("Cache miss for news ID: {$id}, querying database");
        $news = $this->newsRepository->find($id);

        if ($news) {
            $this->cache->set($cacheKey, $news, $this->cacheTtl);
            $this->logger->info("News ID {$id} loaded from repository");
        } else {
            $this->logger->warning("News ID {$id} not found in repository");
        }

        return $news;
    }

    /**
     * @throws \Exception
     */
    public function createNews(array $data): News
    {
        try {
            $news = $this->newsRepository->create($data);
            $this->logger->info('News created', (array)$news);
            $this->dispatcher->dispatch(NewsEvents::NEWS_CREATED, $news);

            return $news;
        } catch (\Exception $e) {
            $this->logger->error('News creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    public function updateNews(int $id, array $data): ?News
    {
        $this->logger->debug("Updating news ID {$id}", $data);
        $result = $this->newsRepository->update($id, $data);

        if ($result) {
            $this->dispatcher->dispatch(NewsEvents::NEWS_UPDATED, ['id' => $id, 'data' => $data]);
            $this->logger->info("News ID {$id} updated successfully");
        } else {
            $this->logger->warning("Failed to update news ID {$id}, news not found");
        }

        return $result;
    }

    public function deleteNews(int $id): bool
    {
        $this->logger->debug("Deleting news ID {$id}");
        $result = $this->newsRepository->delete($id);

        if ($result) {
            $this->dispatcher->dispatch(NewsEvents::NEWS_DELETED, $id);
            $this->logger->info("News ID {$id} deleted successfully");
        } else {
            $this->logger->warning("Failed to delete news ID {$id}, news not found");
        }

        return $result;
    }

    public function getNewsForApi(): array
    {
        $cacheKey = 'news_api_all';
        $news = $this->cache->get($cacheKey);

        if ($news !== null) {
            $this->logger->debug('Cache hit for API news');
        } else {
            $this->logger->debug('Cache miss for API news, querying database');
            $news = $this->newsRepository->all();
            $this->cache->set($cacheKey, $news, 300);
            $this->logger->info('API news loaded from repository', ['count' => count($news)]);
        }

        return $news;
    }

    public function getNewsForPage(): array
    {
        $cacheKey = 'news_page_all';
        $news = $this->cache->get($cacheKey);

        if ($news !== null) {
            $this->logger->debug('Cache hit for page news');
        } else {
            $this->logger->debug('Cache miss for page news, querying database');
            $news = $this->newsRepository->all();
            $this->cache->set($cacheKey, $news, 1800);
            $this->logger->info('Page news loaded from repository', ['count' => count($news)]);
        }

        return $news;
    }
}