<?php

namespace SaaSFormation\Framework\Projects\Infrastructure;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Common\Identity\IdInterface;
use SaaSFormation\Framework\Contracts\Domain\Aggregate;
use SaaSFormation\Framework\Contracts\Domain\DomainEvent;
use SaaSFormation\Framework\Contracts\Domain\DomainEventStream;
use SaaSFormation\Framework\Contracts\Domain\WriteModelRepositoryInterface;

readonly class KurrentBasedWriteModelRepository implements WriteModelRepositoryInterface
{
    public function __construct(private Client $eventStoreClient, private LoggerInterface $logger)
    {

    }

    public function save(Aggregate $aggregate): void
    {
        $streamName = strtolower($aggregate->code()) . '-' . $aggregate->id()->humanReadable();
        $this->pushEvents($streamName, $aggregate->id(), $aggregate->eventStream());
    }

    public function hasAggregate(IdInterface $id): bool
    {
        return true;
    }

    public function pushEvents(string $streamName, IdInterface $aggregateId, DomainEventStream $eventStream): void
    {
        try {
            $this->logTryingToPush($aggregateId);
            $events = array_map(function (DomainEvent $event) {
                return [
                    "eventId" => $event->id()->humanReadable(),
                    "eventType" => $event->code(),
                    "data" => $event->toArray()
                ];
            }, $eventStream->events());
            $this->eventStoreClient->post("/streams/$streamName", [
                'headers' => [
                    'content-type' => 'application/vnd.eventstore.events+json',
                ],
                'body' => json_encode($events),
            ]);
            $this->logPushed($aggregateId);
        } catch (\Throwable $e) {
            $this->logFailedToPush($e, $aggregateId);
            throw $e;
        }
    }

    /**
     * @param IdInterface $aggregateId
     * @return void
     */
    public function logTryingToPush(IdInterface $aggregateId): void
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
    public function logPushed(IdInterface $aggregateId): void
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
    public function logFailedToPush(\Throwable|\Exception $e, IdInterface $aggregateId): void
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