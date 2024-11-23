<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\Bus;

use League\Tactician\CommandBus;
use SaaSFormation\Framework\Contracts\Application\Bus\EventBusInterface;
use SaaSFormation\Framework\Contracts\Domain\DomainEvent;

class TacticianBasedEventBus implements EventBusInterface
{
    public function __construct(private CommandBus $eventBus)
    {
    }

    public function listen(DomainEvent $event): void
    {
        $this->eventBus->handle($event);
    }
}