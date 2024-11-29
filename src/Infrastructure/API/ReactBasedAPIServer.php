<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\API;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Http\Middleware\LimitConcurrentRequestsMiddleware;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Middleware\RequestBodyParserMiddleware;
use React\Http\Middleware\StreamingRequestMiddleware;
use React\Socket\SecureServer;
use React\Socket\SocketServer;
use SaaSFormation\Framework\Contracts\Infrastructure\API\APIServerInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RouterInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RouterProviderInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\KernelInterface;

class ReactBasedAPIServer implements APIServerInterface
{
    private RouterInterface $router;

    public function __construct(private KernelInterface $kernel, RouterProviderInterface $routerProvider, private ServerConfig $config)
    {
        $this->router = $routerProvider->provide($kernel->container());
    }

    public function start(): void
    {
        $loop = Loop::get();

        $http = new HttpServer(
            new StreamingRequestMiddleware(),
            new LimitConcurrentRequestsMiddleware($this->config->maxConcurrentRequests()),
            new RequestBodyBufferMiddleware($this->config->maxRequestSizeInMb() * 1024 * 1024),
            new RequestBodyParserMiddleware(),
            [$this, "processRequest"]
        );

        $host = $this->config->host();
        $port = $this->config->port();
        $socket = new SocketServer("$host:$port", [], $loop);

        $http->on('error', [$this, "processError"]);

        if($this->config->enableSSL()) {
            $secureSocket = new SecureServer($socket, $loop, [
                'local_cert' => $this->config->certificateFilePath(),
                'local_pk' => $this->config->certificatePrivateKeyFilePath(),
                'allow_self_signed' => true,
                'verify_peer' => false,
            ]);
            $http->listen($secureSocket);
        } else {
            $http->listen($socket);
        }

        echo "Server running at https://$host:$port" . PHP_EOL;

        $loop->addSignal(SIGINT, function () use ($loop, $socket) {
            echo "Received Ctrl+C, stopping the server...\n";
            $socket->close();
            $loop->stop();
        });

        $loop->run();
    }

    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->router->route($request);
        } catch (\Throwable $e) {
            return $this->processError($request, $e);
        }
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

        $this->kernel->logger()->error($exception->getMessage(), $data);

        return Response::json([
            'data' => $data
        ]);
    }
}