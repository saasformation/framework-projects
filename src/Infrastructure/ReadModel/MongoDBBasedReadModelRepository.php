<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\ReadModel;

use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Application\ReadModel\ReadModel;
use SaaSFormation\Framework\Contracts\Application\ReadModel\ReadModelRepositoryInterface;
use SaaSFormation\Framework\Contracts\Application\ReadModel\RepositoryCollectionResult;
use SaaSFormation\Framework\Contracts\Common\Identity\UUIDFactoryInterface;

readonly abstract class MongoDBBasedReadModelRepository implements ReadModelRepositoryInterface
{
    private Client $client;

    public function __construct(private MongoDBClientProvider $mongoDBClientProvider, private LoggerInterface $logger, private UUIDFactoryInterface $uuidFactory)
    {
        $this->client = $this->mongoDBClientProvider->provide();
    }

    public function save(ReadModel $readModel): void
    {
        $this->logger->debug("Trying to save a read model", ['read_model_code' => $readModel->code()]);

        $id = $readModel->id();
        if(!$id) {
            $readModel->setId($id = $this->uuidFactory->generate());
        }

        $data['data'] = $readModel->toArray();
        $data['_id'] = $id->humanReadable();

        $this->client
            ->selectDatabase($this->databaseName())
            ->selectCollection($this->collectionName())
            ->updateOne(['_id' => $data['_id']], ['$set' => ['data' => $data['data']]], ['upsert' => true]);

        $this->logger->debug("Read model was saved.", ['read_model_code' => $readModel->code()]);
    }

    public function findOneByCriteria(array $criteria): ?ReadModel
    {
        $this->logger->debug("Trying to find one read model", ['criteria' => $criteria]);
        $result = $this->findByCriteria($criteria);

        if($result->totalResultsRetrieved === 0) {
            $this->logger->warning("Read model not found", ['criteria' => $criteria]);
            throw new \Exception("No results found for the given criteria.");
        }

        $this->logger->debug("One read model found", ['code' => $result->readModels[0]->code(), 'criteria' => $criteria]);
        return $result->readModels[0];
    }

    public function findByCriteria(array $criteria): RepositoryCollectionResult
    {
        $this->logger->debug("Trying to find read models", ['criteria' => $criteria]);
        $readModels = [];

        $totalResults = $this->client
            ->selectDatabase($this->databaseName())
            ->selectCollection($this->collectionName())
            ->countDocuments();

        $results = $this->client
            ->selectDatabase($this->databaseName())
            ->selectCollection($this->collectionName())
            ->find($criteria);

        $results->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

        $items = $results->toArray();

        foreach ($items as $result) {
            $className = $this->readModelClass();
            $readModels[] = $className::fromArray($result['_id'], $result['data']);
        }

        $this->logger->debug("Read models found", ['total' => count($readModels), 'criteria' => $criteria]);

        return new RepositoryCollectionResult($totalResults, $readModels);
    }

    public abstract function databaseName(): string;
    public abstract function collectionName(): string;
    public abstract function readModelClass(): string;
}