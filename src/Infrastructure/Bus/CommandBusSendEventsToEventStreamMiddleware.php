<?php

namespace SaaSFormation\Framework\Projects\Infrastructure\Bus;

use League\Tactician\Middleware;
use SaaSFormation\Framework\Contracts\Application\Bus\EventBusInterface;

class CommandBusSendEventsToEventStreamMiddleware implements Middleware
{
    public function __construct(private EventBusInterface $eventBus)
    {
    }

    public function execute($command, callable $next)
    {
        $domainEventStream = $next($command);

        foreach($domainEventStream as $event) {
            $this->eventBus->listen($event);
        }

        return $domainEventStream;
    }
}