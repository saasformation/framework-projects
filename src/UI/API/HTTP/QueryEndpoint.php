<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\UI\API\HTTP;

use SaaSFormation\Framework\Contracts\Application\Bus\QueryInterface;
use SaaSFormation\Framework\Contracts\Application\Bus\QueryResultInterface;

abstract readonly class QueryEndpoint extends Endpoint
{
    public function ask(QueryInterface $query): QueryResultInterface
    {
        return $this->queryBus->ask($query);
    }
}