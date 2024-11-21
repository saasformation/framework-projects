<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\UI\API\HTTP;

use SaaSFormation\Framework\Contracts\Application\Bus\CommandInterface;

abstract class CommandEndpoint extends Endpoint
{
    protected function handle(CommandInterface $command): void
    {
        $this->commandBus->handle($command);
    }
}