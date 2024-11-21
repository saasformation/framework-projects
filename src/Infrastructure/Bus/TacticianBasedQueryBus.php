<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\Bus;

use League\Tactician\CommandBus;
use SaaSFormation\Framework\Contracts\Application\Bus\QueryBusInterface;
use SaaSFormation\Framework\Contracts\Application\Bus\QueryInterface;
use SaaSFormation\Framework\Contracts\Application\Bus\QueryResultInterface;

readonly class TacticianBasedQueryBus implements QueryBusInterface
{
    public function __construct(private CommandBus $queryBus)
    {
    }

    public function ask(QueryInterface $query): QueryResultInterface
    {
        $queryResult = $this->queryBus->handle($query);

        if(!$queryResult instanceof QueryResultInterface) {
            throw new \Exception("Query handlers must return an instance of QueryResultInterface");
        }

        return $queryResult;
    }
}