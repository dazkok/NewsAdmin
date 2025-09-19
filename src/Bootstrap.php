<?php

namespace App;

use App\Controllers\AuthController;
use App\Controllers\NewsController;
use App\Middleware\CsrfMiddleware;
use App\Repositories\Contracts\NewsRepositoryInterface;
use App\Repositories\NewsRepository;
use App\Services\AuthService;
use App\Services\Database;
use Dotenv\Dotenv;

class Bootstrap
{
    private array $container = [];

    public function __construct(string $envPath, bool $runRoutes = true)
    {
        $dotenv = Dotenv::createImmutable(dirname($envPath));
        $dotenv->load();

        $this->container['config'] = new Config($_ENV);
        $this->container['db'] = new Database($this->container['config']);
        $this->container['view'] = new View();

        $this->container['authService'] = new AuthService(
            $this->container['config']->get('AUTH_USER'),
            $this->container['config']->get('AUTH_PASSWORD')
        );

        $this->container['auth'] = function ($params, $container) {
            $auth = $container['authService'];
            if (!$auth->check()) {
                header('Location: /');
                return false;
            }
            return true;
        };

        $this->container['csrf'] = new Csrf();
        $this->container['csrf_mw'] = new CsrfMiddleware($this->container['csrf']);

        $this->container['authController'] = new AuthController(
            $this->container['authService'],
            $this->container['view'],
            $this->container['csrf']
        );

        $this->container[NewsRepositoryInterface::class] = new NewsRepository($this->container['db']);
        $this->container['newsController'] = new NewsController(
            $this->container[NewsRepositoryInterface::class],
            $this->container['view'],
            $this->container['csrf']
        );

        $this->container['router'] = new Router($this->container);

        if ($runRoutes) {
            $this->registerRoutes();
        }
    }

    private function registerRoutes(): void
    {
        $router = $this->container['router'];

        $router->get('/', 'AuthController@showlogin');
        $router->post('/login', 'AuthController@login');

        $router->get('/admin', 'NewsController@index', ['auth']);
        $router->post('/news/create', 'NewsController@create', ['auth']);
        $router->post('/news/update', 'NewsController@update', ['auth']);
        $router->post('/news/delete', 'NewsController@delete', ['auth']);

        $router->get('/news/{id}', 'NewsController@show', ['auth']);
    }

    public function getRouter(): Router
    {
        return $this->container['router'];
    }

    public function get(string $key)
    {
        return $this->container[$key] ?? null;
    }
}