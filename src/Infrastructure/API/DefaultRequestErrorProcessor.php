<?php

namespace SaaSFormation\Framework\Projects\Infrastructure\API;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use React\Http\Message\Response;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RequestErrorProcessorInterface;
use SaaSFormation\Framework\SharedKernel\UI\HTTP\StatusEnum;

readonly class DefaultRequestErrorProcessor implements RequestErrorProcessorInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function processError(ServerRequestInterface $request, \Throwable $exception): ResponseInterface
    {
        $data = [
            'error' => [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ],
            'request' => [
                'correlation_id' => $request->getHeaderLine('correlation-id'),
                'url' => $request->getUri()->getPath(),
                'user_agent' => $request->getHeaderLine('user-agent')
            ]
        ];

        $this->logger->error($exception->getMessage(), $data);

        return Response::json([
            'data' => $data
        ])->withStatus(StatusEnum::HTTP_GENERAL_ERROR->value);
    }
}