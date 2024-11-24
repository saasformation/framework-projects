<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Common\Identity\IdInterface;
use SaaSFormation\Framework\Contracts\Common\Identity\UUIDFactoryInterface;
use SaaSFormation\Framework\Contracts\Domain\Aggregate;
use SaaSFormation\Framework\Contracts\Domain\DomainEvent;
use SaaSFormation\Framework\Contracts\Domain\DomainEventStream;
use SaaSFormation\Framework\Contracts\Domain\WriteModelRepositoryInterface;

readonly class KurrentBasedWriteModelRepository implements WriteModelRepositoryInterface
{
    private Client $client;

    public function __construct(private KurrentClientProvider $kurrentClientProvider, private LoggerInterface $logger, private UUIDFactoryInterface $uuidFactory)
    {
        $this->client = $this->kurrentClientProvider->provide();
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
                    "eventId" => $event->id() ? $event->id()->humanReadable() : $this->uuidFactory->generate()->humanReadable(),
                    "eventType" => $event->code(),
                    "data" => $event->toArray()
                ];
            }, $eventStream->events());
            $response = $this->client->post("/streams/$streamName", [
                'headers' => [
                    'content-type' => 'application/vnd.eventstore.events+json',
                ],
                'body' => json_encode($events),
            ]);
            if($response->getStatusCode() !== 201) {
                throw new \Exception("Failed to push events for $streamName");
            }
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