<?php

namespace SaaSFormation\Framework\Projects\Infrastructure;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Common\Identity\IdInterface;
use SaaSFormation\Framework\Contracts\Domain\Aggregate;
use SaaSFormation\Framework\Contracts\Domain\DomainEvent;
use SaaSFormation\Framework\Contracts\Domain\WriteModelRepositoryInterface;

readonly class KurrentBasedWriteModelRepository implements WriteModelRepositoryInterface
{
    public function __construct(private Client $eventStoreClient, private LoggerInterface $logger)
    {

    }

    public function save(Aggregate $aggregate): void
    {
        $streamName = strtolower($aggregate->code()) . '-' . $aggregate->id()->humanReadable();
        foreach ($aggregate->eventStream()->events() as $event) {
            $this->pushEvent($streamName, $aggregate->id(), $event);
        }
    }

    public function hasAggregate(IdInterface $id): bool
    {
        return true;
    }

    public function pushEvent(string $streamName, IdInterface $aggregateId, DomainEvent $event): void
    {
        try {
            $this->logTryingToPush($aggregateId, $event);
            $this->eventStoreClient->post("/streams/$streamName", [
                'headers' => [
                    'content-type' => 'application/vnd.eventstore.events+json',
                ],
                'body' => json_encode([
                    [
                        "eventId" => $event->id()->humanReadable(),
                        "eventType" => $event->code(),
                        "data" => $event->toArray()
                    ]
                ])
            ]);
            $this->logPushed($aggregateId, $event);
        } catch (\Throwable $e) {
            $this->logFailedToPush($e, $aggregateId, $event);
            throw $e;
        }
    }

    /**
     * @param IdInterface $aggregateId
     * @param DomainEvent $event
     * @return void
     */
    public function logTryingToPush(IdInterface $aggregateId, DomainEvent $event): void
    {
        $this->logger->debug("Trying to push domain event to the event store", [
            "data" => [
                "aggregateId" => $aggregateId->humanReadable(),
                "eventId" => $event->id()->humanReadable(),
                "eventType" => $event->code()
            ]
        ]);
    }

    /**
     * @param IdInterface $aggregateId
     * @param DomainEvent $event
     * @return void
     */
    public function logPushed(IdInterface $aggregateId, DomainEvent $event): void
    {
        $this->logger->debug("Domain event pushed to the event store", [
            "data" => [
                "aggregateId" => $aggregateId->humanReadable(),
                "eventId" => $event->id()->humanReadable(),
                "eventType" => $event->code()
            ]
        ]);
    }

    /**
     * @param \Throwable|\Exception $e
     * @param IdInterface $aggregateId
     * @param DomainEvent $event
     * @return void
     */
    public function logFailedToPush(\Throwable|\Exception $e, IdInterface $aggregateId, DomainEvent $event): void
    {
        $this->logger->error("Domain event failed to push to the event store", [
            "error" => [
                "message" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
            ],
            "data" => [
                "aggregateId" => $aggregateId->humanReadable(),
                "eventId" => $event->id()->humanReadable(),
                "eventType" => $event->code()
            ]
        ]);
    }
}