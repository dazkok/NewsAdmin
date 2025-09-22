<?php

namespace App\Application;

use App\Application\Listeners\NewsCacheListener;
use App\Domain\Contracts\CacheInterface;
use App\Domain\Contracts\EventDispatcherInterface;
use App\Domain\Contracts\LoggerInterface;
use App\Domain\Contracts\NewsRepositoryInterface;
use App\Domain\Events\EventDispatcher;
use App\Domain\Events\NewsEvents;
use App\Domain\Repositories\NewsRepository;
use App\Domain\Services\AuthService;
use App\Domain\Services\NewsService;
use App\Domain\Validation\Validator;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NewsController;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Response;
use App\Infrastructure\Cache\RedisCache;
use App\Infrastructure\Database\Database;
use App\Infrastructure\Logging\FileLogger;
use App\Infrastructure\Support\Config;
use App\Infrastructure\Support\Csrf;
use App\Infrastructure\Support\View;
use Dotenv\Dotenv;

class Bootstrap
{
    private Container $container;

    /**
     * @throws \Exception
     */
    public function __construct(string $envPath)
    {
        $this->container = Container::getInstance();
        $this->loadEnvironment($envPath);
        $this->registerServices();
        $this->registerControllers();
        $this->registerMiddleware();
        $this->registerRouter();
    }

    private function loadEnvironment(string $envPath): void
    {
        $dotenv = Dotenv::createImmutable(dirname($envPath));
        $dotenv->load();
    }

    /**
     * @throws \Exception
     */
    private function registerServices(): void
    {
        $this->container->set('config', new Config($_ENV));
        $this->container->set('db', new Database($this->container->get('config')));
        $this->container->set('csrf', new Csrf());
        $this->container->set('view', new View($this->container->get('csrf')));

        $this->container->set('validator', new Validator());

        $this->container->set(NewsRepositoryInterface::class, new NewsRepository(
            $this->container->get('db')
        ));

        $this->container->set(LoggerInterface::class, function () {
            $config = $this->container->get('config');
            return new FileLogger(
                $config->get('LOG_PATH', __DIR__ . '/../../../logs/app.log'),
                $config->get('LOG_DATE_FORMAT', 'Y-m-d H:i:s')
            );
        });

        $this->container->set(CacheInterface::class, function () {
            $config = $this->container->get('config');
            return new RedisCache(
                $config->get('REDIS_HOST', 'redis'),
                $config->get('REDIS_PORT', 6379),
                $config->get('REDIS_PREFIX', 'news_')
            );
        });

        $this->container->set(EventDispatcherInterface::class, function () {
            $dispatcher = new EventDispatcher();
            $this->registerEventListeners($dispatcher);
            return $dispatcher;
        });

        $this->container->set('logger', function () {
            return $this->container->get(LoggerInterface::class);
        });

        $this->container->set('authService', new AuthService(
            $this->container->get('config')->get('AUTH_USER'),
            $this->container->get('config')->get('AUTH_PASSWORD')
        ));

        $this->container->set(NewsService::class, function () {
            return new NewsService(
                $this->container->get(NewsRepositoryInterface::class),
                $this->container->get(CacheInterface::class),
                $this->container->get(LoggerInterface::class),
                $this->container->get(EventDispatcherInterface::class),
                $this->container->get('config')->get('CACHE_TTL', 3600)
            );
        });
    }

    private function registerEventListeners(EventDispatcherInterface $dispatcher): void
    {
        $cache = $this->container->get(CacheInterface::class);
        $cacheListener = new NewsCacheListener($cache);

        $dispatcher->listen(NewsEvents::NEWS_CREATED, [$cacheListener, 'handleNewsCreated']);
        $dispatcher->listen(NewsEvents::NEWS_UPDATED, [$cacheListener, 'handleNewsUpdated']);
        $dispatcher->listen(NewsEvents::NEWS_DELETED, [$cacheListener, 'handleNewsDeleted']);
    }

    private function registerControllers(): void
    {
        $this->container->set(AuthController::class, new AuthController(
            $this->container->get('validator')
        ));

        $this->container->set(NewsController::class, new NewsController(
            $this->container->get(NewsService::class),
            $this->container->get('validator')
        ));
    }

    /**
     * @throws \Exception
     */
    private function registerMiddleware(): void
    {
        $this->container->set('csrf_mw', new CsrfMiddleware($this->container->get('csrf')));
        $this->container->set('guest', new RedirectIfAuthenticated());

        $this->container->set('auth', function () {
            $auth = $this->container->get('authService');
            if (!$auth->check()) {
                return new Response('', 302, ['Location' => '/']);
            }
            return true;
        });
    }

    /**
     * @throws \Exception
     */
    private function registerRouter(): void
    {
        $this->container->set('router', new Router($this->container->getServices()));
        $this->registerRoutes();
    }

    /**
     * @throws \Exception
     */
    private function registerRoutes(): void
    {
        $router = $this->container->get('router');

        $routes = require __DIR__ . '/../../routes/web.php';
        $routes($router);
    }

    public function getKernel(): Kernel
    {
        return new Kernel($this->container->getServices());
    }
}