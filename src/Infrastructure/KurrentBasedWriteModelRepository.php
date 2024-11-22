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
        $streamName = 'aggregate-' . strtolower($aggregate->code()) . '-' . $aggregate->id()->humanReadable();
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
            $this->logger->debug("Trying to push domain event to the event store", [
                "data" => [
                    "aggregateId" => $aggregateId->humanReadable(),
                    "eventId" => $event->id()->humanReadable(),
                    "eventType" => $event->code()
                ]
            ]);
            $this->eventStoreClient->post("/streams/$streamName", [
                'headers' => [
                    'content-type' => 'application/vnd.eventstore.events+json',
                ],
                'body' => json_encode([
                    "eventId" => $event->id()->humanReadable(),
                    "eventType" => $event->code(),
                    "data" => $event->toArray()
                ])
            ]);
            $this->logger->debug("Domain event pushed to the event store", [
                "data" => [
                    "aggregateId" => $aggregateId->humanReadable(),
                    "eventId" => $event->id()->humanReadable(),
                    "eventType" => $event->code()
                ]
            ]);
        } catch (\Throwable $e) {
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
}