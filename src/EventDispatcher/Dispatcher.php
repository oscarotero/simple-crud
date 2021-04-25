<?php
declare(strict_types = 1);

namespace SimpleCrud\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class Dispatcher implements EventDispatcherInterface
{
    private $provider;

    public function __construct(ListenerProviderInterface $provider = null)
    {
        $this->provider = $provider ?: new ListenerProvider();
    }

    public function listen(string $type, callable $callback): self
    {
        $this->provider->listen($type, $callback);

        return $this;
    }

    public function dispatch(object $event)
    {
        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        return $event;
    }
}
