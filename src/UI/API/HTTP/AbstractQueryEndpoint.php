<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\UI\API\HTTP;

use SaaSFormation\Framework\SharedKernel\Application\Messages\QueryInterface;
use SaaSFormation\Framework\SharedKernel\Application\Messages\QueryResultInterface;

abstract readonly class AbstractQueryEndpoint extends AbstractEndpoint
{
    public function ask(QueryInterface $query): QueryResultInterface
    {
        return $this->queryBus->ask($query);
    }
}