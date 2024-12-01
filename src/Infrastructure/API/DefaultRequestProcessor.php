<?php

namespace SaaSFormation\Framework\Projects\Infrastructure\API;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RequestErrorProcessorInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RequestProcessorInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RouterInterface;

readonly class DefaultRequestProcessor implements RequestProcessorInterface
{
    public function __construct(private RouterInterface $router, private RequestErrorProcessorInterface $requestErrorProcessor)
    {
    }

    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->router->route($request);
        } catch (\Throwable $e) {
            return $this->requestErrorProcessor->processError($request, $e);
        }
    }
}