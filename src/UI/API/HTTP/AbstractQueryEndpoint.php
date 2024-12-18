<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\UI\API\HTTP;

use Assert\Assert;
use Psr\Http\Message\ServerRequestInterface;
use SaaSFormation\Framework\SharedKernel\Application\Messages\QueryInterface;
use SaaSFormation\Framework\SharedKernel\Application\Messages\QueryResultInterface;
use SaaSFormation\Framework\SharedKernel\Common\Identity\IdInterface;

abstract readonly class AbstractQueryEndpoint extends AbstractEndpoint
{
    public function ask(QueryInterface $query, ServerRequestInterface $request): QueryResultInterface
    {
        $requestId = $request->getAttribute('request_id');
        Assert::that($requestId)->isInstanceOf(IdInterface::class);

        $query->setRequestId($requestId);

        return $this->queryBus->ask($query);
    }
}