<?php

namespace App\Application;

use App\Http\Response;
use ReflectionException;

class Router
{
    private array $routes = [];
    private array $container;
    private array $globalMiddlewares = ['flash', 'csrf_mw'];

    public function __construct(array $container = [])
    {
        $this->container = $container;
    }

    public function get(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function patch(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middlewares);
    }

    public function delete(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, $handler, array $middlewares = []): void
    {
        $method = strtoupper($method);

        if (!isset($this->routes[$method]) || !is_array($this->routes[$method])) {
            $this->routes[$method] = [];
        }

        $path = $this->normalizePath($path);
        [$regex, $paramNames] = $this->compilePathToRegex($path);

        $this->routes[$method][] = [
            'path' => $path,
            'regex' => $regex,
            'paramNames' => $paramNames,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    /**
     * @throws ReflectionException
     */
    public function dispatch(string $method, string $uri): Response
    {
        $method = strtoupper($method);

        if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
            $this->parseJsonInput();
        }

        $path = parse_url($uri, PHP_URL_PATH);
        $path = $this->normalizePath($path ?? '/');

        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $routesForMethod = isset($this->routes[$method]) && is_array($this->routes[$method])
            ? $this->routes[$method]
            : [];

        foreach ($routesForMethod as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($route['paramNames'] as $name) {
                    $params[$name] = $matches[$name] ?? null;
                }

                foreach ($this->globalMiddlewares as $gmw) {
                    $result = $this->runMiddleware($gmw, $params);
                    if ($result instanceof Response) {
                        return $result;
                    }
                    if ($result === false) {
                        return new Response('Middleware blocked', 403);
                    }
                }

                foreach ($route['middlewares'] as $mw) {
                    $result = $this->runMiddleware($mw, $params);
                    if ($result instanceof Response) {
                        return $result;
                    }
                    if ($result === false) {
                        return new Response('Access denied', 403);
                    }
                }

                $callable = $this->resolveHandler($route['handler']);
                if (!is_callable($callable)) {
                    return new Response("Handler for route {$route['path']} is not callable.", 500);
                }

                $result = call_user_func_array($callable, array_values($params));
                return Response::makeFromControllerResult($result);
            }
        }

        return new Response('404 Not Found', 404);
    }

    private function parseJsonInput(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');

            if (!empty($input)) {
                $data = json_decode($input, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    $_REQUEST = array_merge($_REQUEST, $data);

                    $_POST = array_merge($_POST, $data);
                }
            }
        }
    }

    private function normalizePath(string $path): string
    {
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        return $path === '' ? '/' : $path;
    }

    private function compilePathToRegex(string $path): array
    {
        $paramNames = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_-]*)}#', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $path);

        $regex = '#^' . $regex . '$#u';

        return [$regex, $paramNames];
    }

    /**
     * @throws ReflectionException
     */
    private function resolveHandler($handler): callable|array|null
    {
        if (is_callable($handler)) {
            return $handler;
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $fullClass = $this->qualifyControllerClass($class);
            $instance = $this->getFromContainerOrNew($fullClass);
            return [$instance, $method];
        }

        if (is_array($handler) && count($handler) === 2) {
            [$classOrInst, $method] = $handler;
            if (is_string($classOrInst)) {
                $fullClass = $this->qualifyControllerClass($classOrInst);
                $instance = $this->getFromContainerOrNew($fullClass);
                return [$instance, $method];
            }
            return $handler;
        }

        return null;
    }

    private function qualifyControllerClass(string $class): string
    {
        if (class_exists($class)) {
            return $class;
        }

        $prefixed = '\\App\\Controllers\\' . ltrim($class, '\\');
        return class_exists($prefixed) ? $prefixed : $class;
    }

    /**
     * @throws ReflectionException
     */
    private function getFromContainerOrNew(string $class)
    {
        if (isset($this->container[$class])) {
            return $this->container[$class];
        }

        $short = (new \ReflectionClass($class))->getShortName();
        if (isset($this->container[$short])) {
            return $this->container[$short];
        }

        if (!class_exists($class)) {
            throw new \RuntimeException("Controller class {$class} not found");
        }

        $ref = new \ReflectionClass($class);
        $ctor = $ref->getConstructor();
        if ($ctor && $ctor->getNumberOfParameters() === 1) {
            return $ref->newInstance($this->container);
        }

        return $ref->newInstance();
    }

    private function runMiddleware($mw, array $params): Response|bool
    {
        $callable = null;

        if (is_string($mw) && isset($this->container[$mw]) && is_callable($this->container[$mw])) {
            $callable = $this->container[$mw];
        } elseif (is_callable($mw)) {
            $callable = $mw;
        } elseif (is_string($mw) && isset($this->container[$mw])) {
            $svc = $this->container[$mw];
            if (is_object($svc) && method_exists($svc, 'handle')) {
                $callable = [$svc, 'handle'];
            } elseif (is_object($svc) && method_exists($svc, 'check')) {
                $callable = function () use ($svc) {
                    return $svc->check();
                };
            }
        }

        if (!$callable) {
            return true;
        }

        $result = call_user_func($callable, $params, $this->container);

        if ($result instanceof Response) {
            return $result;
        }

        return $result !== false;
    }
}