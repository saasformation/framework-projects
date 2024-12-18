<?php

namespace SaaSFormation\Framework\Projects\Infrastructure\API;

use Assert\Assert;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RequestErrorProcessorInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RequestProcessorInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RouterInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\KernelInterface;
use SaaSFormation\Framework\MongoDBBasedReadModel\Infrastructure\ReadModel\MongoDBClient;
use SaaSFormation\Framework\SharedKernel\Common\Identity\UUIDFactoryInterface;
use SaaSFormation\Framework\SharedKernel\UI\HTTP\StatusEnum;

readonly class DefaultRequestProcessor implements RequestProcessorInterface
{
    public function __construct(
        private RouterInterface                $router,
        private RequestErrorProcessorInterface $requestErrorProcessor,
        private KernelInterface                $kernel,
        private MongoDBClient                  $mongoDBClient
    )
    {
    }

    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $uuidFactory = $this->kernel->container()->get(UUIDFactoryInterface::class);
            Assert::that($uuidFactory)->isInstanceOf(UUIDFactoryInterface::class);

            $requestId = $request->getHeaderLine('request-id');
            if ($requestId) {
                $requestId = $uuidFactory->fromString($requestId);
            } else {
                $requestId = $uuidFactory->generate();
            }
            $correlationId = $request->getHeaderLine('correlation-id');
            if ($correlationId) {
                $correlationId = $uuidFactory->fromString($correlationId);
            } else {
                $correlationId = $uuidFactory->generate();
            }
            $executorId = $uuidFactory->generate();

            $request = $request->withAttribute('request_id', $requestId)
                ->withAttribute('correlation_id', $correlationId)
                ->withAttribute('executor_id', $executorId);

            $this->mongoDBClient->startSession($requestId);

            try {
                $route = $this->router->route($request);
            } catch (\Throwable $e) {
                $this->mongoDBClient->endSession($requestId);
                throw $e;
            }

            $this->mongoDBClient->endSession($requestId);

            return $route;
        } catch (MethodNotAllowedException) {
            return Response::json(null)->withStatus(StatusEnum::HTTP_METHOD_NOT_ALLOWED->value);
        } catch (NotFoundException) {
            return Response::json(null)->withStatus(StatusEnum::HTTP_NOT_FOUND->value);
        } catch (\Throwable $e) {
            return $this->requestErrorProcessor->processError($request, $e);
        }
    }
}