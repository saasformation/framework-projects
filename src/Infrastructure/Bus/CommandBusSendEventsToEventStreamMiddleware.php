<?php

namespace SaaSFormation\Framework\Projects\Infrastructure\Bus;

use League\Tactician\Exception\CanNotInvokeHandlerException;
use League\Tactician\Handler\CommandNameExtractor\CommandNameExtractor;
use League\Tactician\Handler\Locator\HandlerLocator;
use League\Tactician\Handler\MethodNameInflector\MethodNameInflector;
use League\Tactician\Middleware;
use SaaSFormation\Framework\Contracts\Application\Bus\EventBusInterface;

class CommandBusSendEventsToEventStreamMiddleware implements Middleware
{
    public function __construct(
        private EventBusInterface $eventBus,
        private CommandNameExtractor $commandNameExtractor,
        private HandlerLocator $handlerLocator,
        private MethodNameInflector $methodNameInflector)
    {
    }

    public function execute($command, callable $next)
    {
        $domainEventStream = $next($command);

        foreach($domainEventStream as $event) {
            $this->eventBus->listen($event);
        }

        $commandName = $this->commandNameExtractor->extract($command);
        $handler = $this->handlerLocator->getHandlerForCommand($commandName);
        $methodName = $this->methodNameInflector->inflect($command, $handler);

        // is_callable is used here instead of method_exists, as method_exists
        // will fail when given a handler that relies on __call.
        if (!is_callable([$handler, $methodName])) {
            throw CanNotInvokeHandlerException::forCommand(
                $command,
                "Method '{$methodName}' does not exist on handler"
            );
        }

        return $handler->{$methodName}($command);
    }
}