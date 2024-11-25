<?php

namespace SaaSFormation\Framework\Projects\Infrastructure;

use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Common\Identity\IdInterface;
use SaaSFormation\Framework\Contracts\Common\Identity\UUIDFactoryInterface;
use SaaSFormation\Framework\Contracts\Domain\Aggregate;
use SaaSFormation\Framework\Contracts\Domain\WriteModelRepositoryInterface;

readonly class MySQLBasedWriteModelRepository extends WriteModelRepository implements WriteModelRepositoryInterface
{
    private \PDO $client;

    public function __construct(private MySQLClientProvider $mySQLClientProvider, LoggerInterface $logger, private UUIDFactoryInterface $uuidFactory)
    {
        parent::__construct($logger);

        $this->client = $this->mySQLClientProvider->provide();
    }

    public function save(Aggregate $aggregate): void
    {
        foreach ($aggregate->eventStream()->events() as $event) {
            $this->logTryingToPush($aggregate->id());
            $this->client->beginTransaction();

            try {
                $this->client->prepare(
                    "INSERT INTO eventstore (id, aggregate_id, aggregate_code, event_code, event_version, event_data, created_at) values (:id, :aggregate_id, :aggregate_code, :event_code, :event_version, :event_data, :created_at)"
                )->execute([
                    'id' => $event->id() ? $event->id()->humanReadable() : $this->uuidFactory->generate()->humanReadable(),
                    'aggregate_id' => $aggregate->id()->humanReadable(),
                    'aggregate_code' => $aggregate->code(),
                    'event_code' => $event->code(),
                    'event_version' => $event->version(),
                    'event_data' => json_encode($event->toArray()),
                    'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.u'),
                ]);

                $this->client->commit();
                $this->logPushed($aggregate->id());
            } catch (\Throwable $e) {
                $this->logFailedToPush($e, $aggregate->id());
                $this->client->rollBack();
                throw new \Exception($e->getMessage());
            }
        }
    }

    public function hasAggregate(IdInterface $id): bool
    {
        $query = $this->client->prepare("SELECT * FROM eventstore WHERE aggregate_id = :id");
        $query->execute([
            'id' => $id->humanReadable(),
        ]);
        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        return count($results) > 0;
    }
}