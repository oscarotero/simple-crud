<?php

namespace SimpleCrud\Tests\EventDispatcher;

use Psr\EventDispatcher\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    private $listeners = [];

    public function listen(string $type, callable $callback): self
    {
        $listeners = $this->listeners[$type] ?? [];
        $listeners[] = $callback;
        $this->listeners[$type] = $listeners;

        return $this;
    }

    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->listeners as $type => $listeners) {
            if ($event instanceof $type) {
                return $listeners;
            }
        }
    }
}
