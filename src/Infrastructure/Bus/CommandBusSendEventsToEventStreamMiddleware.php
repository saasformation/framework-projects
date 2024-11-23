<?php

namespace SaaSFormation\Framework\Projects\Infrastructure\Bus;

use League\Tactician\Exception\CanNotInvokeHandlerException;
use League\Tactician\Handler\CommandNameExtractor\CommandNameExtractor;
use League\Tactician\Handler\Locator\HandlerLocator;
use League\Tactician\Handler\MethodNameInflector\MethodNameInflector;
use League\Tactician\Middleware;
use SaaSFormation\Framework\Contracts\Application\Bus\EventBusInterface;
use SaaSFormation\Framework\Contracts\Application\EventDispatcher\EventDispatcherInterface;
use SaaSFormation\Framework\Contracts\Domain\DomainEventStream;

class CommandBusSendEventsToEventStreamMiddleware implements Middleware
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private CommandNameExtractor     $commandNameExtractor,
        private HandlerLocator           $handlerLocator,
        private MethodNameInflector      $methodNameInflector)
    {
    }

    public function execute($command, callable $next)
    {
        $commandName = $this->commandNameExtractor->extract($command);
        $handler = $this->handlerLocator->getHandlerForCommand($commandName);
        $methodName = $this->methodNameInflector->inflect($command, $handler);

        /** @var DomainEventStream $domainEventStream */
        $domainEventStream = $handler->{$methodName}($command);

        foreach($domainEventStream->events() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}