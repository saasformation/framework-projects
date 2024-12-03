<?php

namespace SaaSFormation\Framework\Projects\Infrastructure\API;

use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RequestErrorProcessorInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RequestProcessorInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RouterInterface;
use SaaSFormation\Framework\Contracts\UI\HTTP\StatusEnum;

readonly class DefaultRequestProcessor implements RequestProcessorInterface
{
    public function __construct(private RouterInterface $router, private RequestErrorProcessorInterface $requestErrorProcessor)
    {
    }

    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->router->route($request);
        } catch(MethodNotAllowedException) {
            return Response::json(null)->withStatus(StatusEnum::HTTP_METHOD_NOT_ALLOWED->value);
        } catch(NotFoundException) {
            return Response::json(null)->withStatus(StatusEnum::HTTP_NOT_FOUND->value);
        } catch (\Throwable $e) {
            return $this->requestErrorProcessor->processError($request, $e);
        }
    }
}