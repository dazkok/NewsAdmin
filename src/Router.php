<?php

namespace App;

use ReflectionException;

class Router
{
    private array $routes = [];
    private array $container;
    private array $globalMiddlewares = ['csrf_mw'];

    public function __construct(array $container)
    {
        $this->container = $container;
    }

    public function get(string $path, callable $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, callable $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, $handler, array $middlewares = []): void
    {
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
    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = $this->normalizePath(parse_url($uri, PHP_URL_PASS ?? '/'));

        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $routesForMethod = $this->routes[$method] ?? [];

        foreach ($routesForMethod as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($route['paramNames'] as $name) {
                    $params[] = $matches[$name] ?? null;
                }

                foreach ($this->globalMiddlewares as $gmw) {
                    $ok = $this->runMiddleware($gmw, $params);
                    if ($ok === false) return;
                }
                
                foreach ($route['middlewares'] as $mw) {
                    $ok = $this->runMiddleware($mw, $params);
                    if ($ok === false) {
                        return;
                    }
                }

                $callable = $this->resolveHandler($route['handler']);
                if (!is_callable($callable)) {
                    http_response_code(500);
                    echo "Handler for route {$route['path']} is not callable.";
                    return;
                }

                call_user_func_array($callable, $params);
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
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

    private function runMiddleware($mw, array $params)
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

        return call_user_func($callable, $params, $this->container);
    }
}