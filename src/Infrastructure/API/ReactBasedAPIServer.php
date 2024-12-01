<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\API;

use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Middleware\LimitConcurrentRequestsMiddleware;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Middleware\RequestBodyParserMiddleware;
use React\Http\Middleware\StreamingRequestMiddleware;
use React\Socket\SecureServer;
use React\Socket\SocketServer;
use SaaSFormation\Framework\Contracts\Infrastructure\API\APIServerInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RequestErrorProcessorInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RequestProcessorInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RouterInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RouterProviderInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\KernelInterface;

readonly class ReactBasedAPIServer implements APIServerInterface
{
    private RouterInterface $router;

    public function __construct(private KernelInterface $kernel, RouterProviderInterface $routerProvider, private ServerConfig $config)
    {
        $this->router = $routerProvider->provide($kernel->container());
    }

    public function start(?RequestProcessorInterface $requestProcessor = null, ?RequestErrorProcessorInterface $requestErrorProcessor = null): void
    {
        if(!$requestErrorProcessor) {
            $requestErrorProcessor = new DefaultRequestErrorProcessor($this->kernel->logger());
        }

        if(!$requestProcessor) {
            $requestProcessor = new DefaultRequestProcessor($this->router, $requestErrorProcessor);
        }

        $loop = Loop::get();

        $http = new HttpServer(
            new StreamingRequestMiddleware(),
            new LimitConcurrentRequestsMiddleware($this->config->maxConcurrentRequests()),
            new RequestBodyBufferMiddleware($this->config->maxRequestSizeInMb() * 1024 * 1024),
            new RequestBodyParserMiddleware(),
            [$requestProcessor, "processRequest"]
        );

        $host = $this->config->host();
        $port = $this->config->port();
        $socket = new SocketServer("$host:$port", [], $loop);

        $http->on('error', [$requestErrorProcessor, "processError"]);

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
}