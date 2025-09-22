<?php

namespace App\Domain\Contracts;

interface EventDispatcherInterface
{
    public function dispatch(string $eventName, $payload = null): void;

    public function listen(string $eventName, callable $listener): void;
}