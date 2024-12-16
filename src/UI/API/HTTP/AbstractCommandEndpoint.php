<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\UI\API\HTTP;

use Assert\Assert;
use Psr\Http\Message\ServerRequestInterface;
use SaaSFormation\Framework\SharedKernel\Application\Messages\CommandInterface;
use SaaSFormation\Framework\SharedKernel\Common\Identity\IdInterface;

abstract readonly class AbstractCommandEndpoint extends AbstractEndpoint
{
    protected function handle(CommandInterface $command, ServerRequestInterface $request): void
    {
        $requestId = $request->getAttribute('request_id');
        $correlationId = $request->getAttribute('correlation_id');
        $executorId = $request->getAttribute('executor_id');

        Assert::that($requestId)->isInstanceOf(IdInterface::class);
        Assert::that($correlationId)->isInstanceOf(IdInterface::class);
        Assert::that($executorId)->isInstanceOf(IdInterface::class);

        $command->setRequestId($requestId);
        $command->setCorrelationId($correlationId);
        $command->setExecutorId($executorId);
        $this->commandBus->handle($command);
    }
}