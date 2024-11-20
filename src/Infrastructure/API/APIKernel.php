<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\API;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use React\Http\Message\Response;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RouterInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RouterProviderInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\ContainerProviderInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\EnvVarsManagerInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\EnvVarsManagerProviderInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\KernelInterface;
use SaaSFormation\Framework\EnvVarsManager\Infrastructure\EnvVarsManager;
use Throwable;

class APIKernel implements KernelInterface
{
    private ?ContainerInterface $container = null;
    private ?RouterInterface $router = null;
    private ?EnvVarsManagerInterface $envVarsManager = null;
    private Logger $defaultLogger;

    public function __construct()
    {
        $this->defaultLogger = new Logger('default');

        $logLevel = Level::Debug;

        if(getenv('LOG_LEVEL') !== false && in_array(getenv('LOG_LEVEL'), ['Debug', 'Info', 'Notice', 'Warning', 'Error', 'Critical', 'Alert', 'Emergency'])) {
            $logLevel = Level::fromName(getenv('LOG_LEVEL'));
        }

        $handler = new StreamHandler('php://stdout',  $logLevel);
        $handler->setFormatter(new JsonFormatter());
        $this->defaultLogger->pushHandler($handler);

        if(!getenv('LOG_LEVEL')) {
            $this->defaultLogger->critical("No log level has set via LOG_LEVEL env var");
            die();
        }
    }

    /**
     * @throws \Exception
     */
    public function start(): void
    {
        $this->checkContainerIsSet();
        $this->checkRouterIsSet();
    }

    public function withEnvVars(EnvVarsManagerProviderInterface $envVarsManagerProvider): static
    {
        try {
            $this->envVarsManager = $envVarsManagerProvider->provide();
        } catch(\Exception $e) {
            $this->defaultLogger->critical($e->getMessage());
            die();
        }

        return $this;
    }

    public function withContainer(ContainerProviderInterface $containerProvider): static
    {
        $this->container = $containerProvider->provide($this->defaultLogger);

        return $this;
    }

    public function withRouter(RouterProviderInterface $routerProvider): static
    {
        if(!$this->container) {
            throw new \Exception("Container must be set before initializing router");
        }

        $this->router = $routerProvider->provide($this->container);

        return $this;
    }

    public function envVarsManager(): EnvVarsManagerInterface
    {
        if(!$this->envVarsManager) {
            throw new \Exception("Env vars manager must be set before trying to access it");
        }

        return $this->envVarsManager;
    }

    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        if(!$this->router) {
            throw new \Exception("Router must be set before trying to access it");
        }

        try {
            return $this->router->route($request);
        } catch (\Exception $e) {
            return $this->processError($request, $e);
        }
    }

    public function processError(ServerRequestInterface $request, Throwable $exception): ResponseInterface
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

        $this->defaultLogger->error($exception->getMessage(), $data);

        return Response::json([
            'data' => $data
        ]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function checkContainerIsSet(): void
    {
        if (!$this->container) {
            throw new \Exception("No container has been set; remember to call withContainer method");
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function checkRouterIsSet(): void
    {
        if (!$this->router) {
            throw new \Exception("No router has been set; remember to call withRouter method");
        }
    }

    public function logger(): LoggerInterface
    {
        return $this->defaultLogger;
    }
}