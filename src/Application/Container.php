<?php

namespace App\Application;

class Container
{
    private static ?self $instance = null;
    private array $services = [];

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key)
    {
        if (!$this->has($key)) {
            throw new \Exception("Service {$key} not found in container");
        }

        if (is_callable($this->services[$key])) {
            return $this->services[$key]($this);
        }

        return $this->services[$key];
    }

    public function set(string $key, $value): void
    {
        $this->services[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($this->services[$key]);
    }

    public function getServices(): array
    {
        return $this->services;
    }
}