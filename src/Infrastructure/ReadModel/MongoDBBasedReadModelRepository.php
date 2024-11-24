<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\ReadModel;

use MongoDB\Client;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Application\ReadModel\ReadModel;
use SaaSFormation\Framework\Contracts\Application\ReadModel\ReadModelRepositoryInterface;

readonly abstract class MongoDBBasedReadModelRepository implements ReadModelRepositoryInterface
{
    private Client $client;

    public function __construct(private MongoDBClientProvider $mongoDBClientProvider, private LoggerInterface $logger)
    {
        $this->client = $this->mongoDBClientProvider->provide();
    }

    public function save(ReadModel $readModel): void
    {
        $this->logger->debug("Trying to save a read model", ['read_model_code' => $readModel->code()]);
        $this->client
            ->selectDatabase($this->databaseName())
            ->selectCollection($this->collectionName())
            ->insertOne($readModel);
        $this->logger->debug("Read model was saved.", ['read_model_code' => $readModel->code()]);
    }

    public function findOneByCriteria(array $criteria): ?ReadModel
    {
        $this->logger->debug("Trying to find one read model", ['criteria' => $criteria]);
        $readModels = $this->findByCriteria($criteria);

        if(count($readModels) === 0) {
            $this->logger->warning("Read model not found", ['criteria' => $criteria]);
            throw new \Exception("No results found for the given criteria.");
        }

        $this->logger->debug("One read model found", ['code' => $readModels[0]->code(), 'criteria' => $criteria]);
        return $readModels[0];
    }

    public function findByCriteria(array $criteria): array
    {
        $this->logger->debug("Trying to find read models", ['criteria' => $criteria]);
        $readModels = [];

        $results = $this->client
            ->selectDatabase($this->databaseName())
            ->selectCollection($this->collectionName())
            ->find($criteria)
            ->toArray();

        foreach ($results as $result) {
            $className = $this->readModelClass();
            $readModels[] = $className::fromArray($result->toArray());
        }

        $this->logger->debug("Read models found", ['total' => count($readModels), 'criteria' => $criteria]);
        return $readModels;
    }

    public abstract function databaseName(): string;
    public abstract function collectionName(): string;
    public abstract function readModelClass(): string;
}