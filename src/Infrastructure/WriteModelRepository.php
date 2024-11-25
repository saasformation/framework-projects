<?php

namespace SaaSFormation\Framework\Projects\Infrastructure;

use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Common\Identity\IdInterface;

readonly abstract class WriteModelRepository
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param IdInterface $aggregateId
     * @return void
     */
    protected function logTryingToPush(IdInterface $aggregateId): void
    {
        $this->logger->debug("Trying to push domain events to the event store", [
            "data" => [
                "aggregateId" => $aggregateId->humanReadable()
            ]
        ]);
    }

    /**
     * @param IdInterface $aggregateId
     * @return void
     */
    protected function logPushed(IdInterface $aggregateId): void
    {
        $this->logger->debug("Domain events pushed to the event store", [
            "data" => [
                "aggregateId" => $aggregateId->humanReadable()
            ]
        ]);
    }

    /**
     * @param \Throwable|\Exception $e
     * @param IdInterface $aggregateId
     * @return void
     */
    protected function logFailedToPush(\Throwable|\Exception $e, IdInterface $aggregateId): void
    {
        $this->logger->error("Domain events failed to push to the event store", [
            "error" => [
                "message" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
            ],
            "data" => [
                "aggregateId" => $aggregateId->humanReadable()
            ]
        ]);
    }
}