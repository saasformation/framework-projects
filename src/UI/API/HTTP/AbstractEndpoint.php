<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\UI\API\HTTP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use SaaSFormation\ArrayByPath\RetrieveArrayValueByPathService;
use SaaSFormation\Field\StandardField;
use SaaSFormation\Framework\MessageBus\Application\CommandBusInterface;
use SaaSFormation\Framework\MessageBus\Application\QueryBusInterface;
use SaaSFormation\Framework\Projects\UI\API\HTTP\Attributes\StatusCode;
use SaaSFormation\Framework\SharedKernel\Domain\DuplicatedAggregateException;
use SaaSFormation\Framework\SharedKernel\UI\HTTP\BadRequestException;
use SaaSFormation\Framework\SharedKernel\UI\HTTP\EndpointInterface;
use SaaSFormation\Framework\SharedKernel\UI\HTTP\NoResponderAvailableForAcceptHeaderException;
use SaaSFormation\Framework\SharedKernel\UI\HTTP\NotEmptyResponseBodyInterface;
use SaaSFormation\Framework\SharedKernel\UI\HTTP\ResponderInterface;
use SaaSFormation\Framework\SharedKernel\UI\HTTP\StatusEnum;

abstract readonly class AbstractEndpoint implements EndpointInterface
{
    /**
     * @param string $defaultResponseContentType
     * @param LoggerInterface $logger
     * @param CommandBusInterface $commandBus
     * @param QueryBusInterface $queryBus
     * @param RetrieveArrayValueByPathService $retrieveArrayValueByPathService
     * @param array<string, ResponderInterface> $responders
     */
    public function __construct(
        private string                          $defaultResponseContentType,
        private LoggerInterface                 $logger,
        protected CommandBusInterface           $commandBus,
        protected QueryBusInterface             $queryBus,
        private RetrieveArrayValueByPathService $retrieveArrayValueByPathService,
        private array                           $responders)
    {
    }

    public function doExecute(ServerRequestInterface $request): ResponseInterface
    {
        $responder = $this->responders[$this->defaultResponseContentType];

        try {
            return $responder->respond($this->getResponseDefaultStatusCode(), $this->getResponseBodyData($request));
        } catch (NoResponderAvailableForAcceptHeaderException) {
            return $responder->respond(StatusEnum::HTTP_NOT_ACCEPTABLE);
        } catch(BadRequestException $e) {
            return $responder->respond(StatusEnum::HTTP_BAD_REQUEST, [
                "data" => [
                    "error" => [
                        "message" => $e->getMessage(),
                        "details" => $e->requestErrors()
                    ]
                ]
            ]);
        } catch (DuplicatedAggregateException) {
            return $responder->respond(StatusEnum::HTTP_CONFLICT);
        }
    }

    public function getResponder(ServerRequestInterface $request): ResponderInterface
    {
        $responder = null;
        $accept = explode(',', $request->getHeaderLine('Accept'));

        foreach ($accept as $format) {
            if (isset($this->responders[$format])) {
                $responder = $this->responders[$request->getHeaderLine('Accept')];
            }
        }

        if(!$responder) {
            throw new NoResponderAvailableForAcceptHeaderException();
        }

        return $responder;
    }

    /**
     * @param ServerRequestInterface $request
     * @return mixed[]|null
     */
    public function getResponseBodyData(ServerRequestInterface $request): ?array
    {
        $responseBody = $this->execute($request);
        $data = null;
        if ($responseBody instanceof NotEmptyResponseBodyInterface) {
            $data = $responseBody->toArray();
        }
        return $data;
    }

    /**
     * @throws BadRequestException
     */
    protected function body(ServerRequestInterface $request, string $path): StandardField
    {
        return $this->getFromBodyRequestByPath($request, $path);
    }

    /**
     * @throws BadRequestException
     */
    private function getFromBodyRequestByPath(ServerRequestInterface $request, string $path): StandardField
    {
        $body = $request->getBody();
        $body->rewind();
        $body = json_decode($body->getContents(), true);

        if (!is_array($body)) {
            throw new BadRequestException(["body" => "Body is null or is not a valid json"]);
        }

        return new StandardField($this->retrieveArrayValueByPathService->find($path, $body));
    }

    private function getResponseDefaultStatusCode(): StatusEnum
    {
        $reflectedMethod = new ReflectionMethod(get_class($this), 'execute');
        $attributes = $reflectedMethod->getAttributes(StatusCode::class);

        if (count($attributes) === 1) {
            $statusCode = $attributes[0]->getArguments()[0];
        } else {
            $statusCode = StatusEnum::HTTP_OK;
            $this->logger->warning('Endpoint ' . get_class($this) . ' has no success status code set');
        }

        return $statusCode;
    }
}