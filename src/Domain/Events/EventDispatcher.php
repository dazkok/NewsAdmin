<?php

namespace App\Domain\Events;

use App\Domain\Contracts\EventDispatcherInterface;

class EventDispatcher implements EventDispatcherInterface
{
    private array $listeners = [];

    public function dispatch(string $eventName, $payload = null): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            $listener($payload);
        }
    }

    public function listen(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }
}