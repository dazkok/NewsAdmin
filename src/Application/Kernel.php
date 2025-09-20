<?php

namespace App\Application;

use App\Http\Response;
use Throwable;

class Kernel
{
    protected array $container;

    public function __construct(array $container)
    {
        $this->container = $container;
    }

    public function handle($request): Response
    {
        try {
            $method = $request['method'] ?? $_SERVER['REQUEST_METHOD'];
            $uri = $request['uri'] ?? $_SERVER['REQUEST_URI'];

            if (!isset($this->container['router'])) {
                return new Response('Router not configured', 500);
            }

            return $this->container['router']->dispatch($method, $uri);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    protected function handleException(Throwable $e): Response
    {
        if ($_ENV['APP_ENV'] === 'dev') {
            $content = "Error: " . $e->getMessage() . "<pre>" . $e->getTraceAsString() . "</pre>";
        } else {
            $content = "Internal Server Error";
        }

        return new Response($content, 500);
    }
}