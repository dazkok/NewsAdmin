<?php

namespace App\Application;

use App\Domain\Repositories\Contracts\NewsRepositoryInterface;
use App\Domain\Repositories\NewsRepository;
use App\Domain\Services\AuthService;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NewsController;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Response;
use App\Infrastructure\Database\Database;
use App\Support\Config;
use App\Support\Csrf;
use App\Support\View;
use Dotenv\Dotenv;

class Bootstrap
{
    private Container $container;

    /**
     * @throws \Exception
     */
    public function __construct(string $envPath, bool $runRoutes = true)
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

        $this->container->set('authService', new AuthService(
            $this->container->get('config')->get('AUTH_USER'),
            $this->container->get('config')->get('AUTH_PASSWORD')
        ));

        $this->container->set(NewsRepositoryInterface::class, new NewsRepository(
            $this->container->get('db')
        ));
    }

    private function registerControllers(): void
    {
        $this->container = Container::getInstance();

        $this->container->set(AuthController::class, new AuthController());
//        $container->set(NewsController::class, new NewsController());
    }

    /**
     * @throws \Exception
     */
    private function registerMiddleware(): void
    {
        $this->container->set('csrf_mw', new CsrfMiddleware($this->container->get('csrf')));

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