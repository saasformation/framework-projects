<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\Bus;

use League\Tactician\CommandBus;
use SaaSFormation\Framework\Contracts\Application\Bus\CommandBusInterface;
use SaaSFormation\Framework\Contracts\Application\Bus\CommandInterface;
use SaaSFormation\Framework\Contracts\Domain\DomainEventStream;

readonly class TacticianBasedCommandBus implements CommandBusInterface
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    public function handle(CommandInterface $command): void
    {
        $this->commandBus->handle($command);
    }
}