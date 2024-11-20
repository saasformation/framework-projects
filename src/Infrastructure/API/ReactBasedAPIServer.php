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
use SaaSFormation\Framework\Contracts\Infrastructure\KernelInterface;

class ReactBasedAPIServer implements APIServerInterface
{
    private KernelInterface $kernel;
    private string $host;
    private string $port;
    private int $maxConcurrentRequests;
    private int $maxRequestSizeInMb;
    private bool $sslEnabled = false;
    private string $certificateFilePath;
    private string $certificatePrivateKeyFilePath;

    public function withKernel(KernelInterface $kernel): static
    {
        $this->kernel = $kernel;

        return $this;
    }

    public function withConfig(string $host = null, string $port = null, int $maxConcurrentRequests = null, int $maxRequestSizeInMb = null): static
    {
        if (!$host) {
            if ($this->kernel->envVarsManager()->has('HOST')) {
                $host = $this->kernel->envVarsManager()->get('HOST');
                if(!is_string($host)) {
                    throw new \InvalidArgumentException('HOST env var must be a string');
                }
            } else {
                $this->kernel->logger()->critical("No host was provided, neither through method call or env var 'HOST'");
                die();
            }
        }

        if (!$port) {
            if ($this->kernel->envVarsManager()->has('PORT')) {
                $port = $this->kernel->envVarsManager()->get('PORT');
                if(!is_string($port)) {
                    throw new \InvalidArgumentException('PORT env var must be a string');
                }
            } else {
                $this->kernel->logger()->critical("No port was provided, neither through method call or env var 'PORT'");
                die();
            }
        }

        if (!$maxConcurrentRequests) {
            if ($this->kernel->envVarsManager()->has('MAX_CONCURRENT_REQUESTS')) {
                $maxConcurrentRequests = $this->kernel->envVarsManager()->get('MAX_CONCURRENT_REQUESTS');
                if(!is_int($maxConcurrentRequests)) {
                    throw new \InvalidArgumentException('MAX_CONCURRENT_REQUESTS env var must be a string');
                }
            } else {
                $this->kernel->logger()->critical("No max concurrent requests was provided, neither through method call or env var 'MAX_CONCURRENT_REQUESTS'");
                die();
            }
        }

        if (!$maxRequestSizeInMb) {
            if ($this->kernel->envVarsManager()->has('MAX_REQUEST_SIZE_IN_MB')) {
                $maxRequestSizeInMb = $this->kernel->envVarsManager()->get('MAX_REQUEST_SIZE_IN_MB');
                if(!is_int($maxRequestSizeInMb)) {
                    throw new \InvalidArgumentException('MAX_REQUEST_SIZE_IN_MB env var must be a string');
                }
            } else {
                $this->kernel->logger()->critical("No max request size in mb was provided, neither through method call or env var 'MAX_REQUEST_SIZE_IN_MB'");
                die();
            }
        }

        $this->host = $host;
        $this->port = $port;
        $this->maxConcurrentRequests = $maxConcurrentRequests;
        $this->maxRequestSizeInMb = $maxRequestSizeInMb;

        return $this;
    }

    public function withSSLEnabled(string $certificateFilePath = null, string $certificatePrivateKeyFilePath = null): static
    {
        $this->sslEnabled = true;

        if (!$certificateFilePath) {
            if ($this->kernel->envVarsManager()->has('CERTIFICATE_FILE_PATH')) {
                $certificateFilePath = $this->kernel->envVarsManager()->get('CERTIFICATE_FILE_PATH');
                if(!is_string($certificateFilePath)) {
                    throw new \InvalidArgumentException('CERTIFICATE_FILE_PATH env var must be a string');
                }
            } else {
                $this->kernel->logger()->critical("You enabled ssl but no certificate file path was provided, neither through method call or env var 'CERTIFICATE_FILE_PATH'");
                die();
            }
        }

        if (!$certificatePrivateKeyFilePath) {
            if ($this->kernel->envVarsManager()->has('CERTIFICATE_PRIVATE_KEY_FILE_PATH')) {
                $certificatePrivateKeyFilePath = $this->kernel->envVarsManager()->get('CERTIFICATE_PRIVATE_KEY_FILE_PATH');
                if(!is_string($certificatePrivateKeyFilePath)) {
                    throw new \InvalidArgumentException('CERTIFICATE_PRIVATE_KEY_FILE_PATH env var must be a string');
                }
            } else {
                $this->kernel->logger()->critical("You enabled ssl but no certificate private key file path was provided, neither through method call or env var 'CERTIFICATE_PRIVATE_KEY_FILE_PATH'");
                die();
            }
        }

        $this->certificateFilePath = $certificateFilePath;
        $this->certificatePrivateKeyFilePath = $certificatePrivateKeyFilePath;

        return $this;
    }

    public function start(): void
    {
        $this->kernel->start();

        $loop = Loop::get();

        $http = new HttpServer(
            new StreamingRequestMiddleware(),
            new LimitConcurrentRequestsMiddleware($this->maxConcurrentRequests),
            new RequestBodyBufferMiddleware($this->maxRequestSizeInMb * 1024 * 1024),
            new RequestBodyParserMiddleware(),
            [$this->kernel, "processRequest"]
        );

        $socket = new SocketServer("$this->host:$this->port", [], $loop);

        $http->on('error', [$this->kernel, "processError"]);

        if($this->sslEnabled) {
            $secureSocket = new SecureServer($socket, $loop, [
                'local_cert' => $this->certificateFilePath,
                'local_pk' => $this->certificatePrivateKeyFilePath,
                'allow_self_signed' => true,
                'verify_peer' => false,
            ]);
            $http->listen($secureSocket);
        } else {
            $http->listen($socket);
        }

        echo "Server running at https://$this->host:$this->port" . PHP_EOL;

        $loop->addSignal(SIGINT, function () use ($loop, $socket) {
            echo "Received Ctrl+C, stopping the server...\n";
            $socket->close();
            $loop->stop();
        });

        $loop->run();
    }
}