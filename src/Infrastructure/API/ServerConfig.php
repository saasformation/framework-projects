<?php

namespace SaaSFormation\Framework\Projects\Infrastructure\API;

use SaaSFormation\Framework\Contracts\Infrastructure\KernelInterface;

class ServerConfig
{
    private string $host;
    private string $port;
    private int $maxConcurrentRequests;
    private int $maxRequestSizeInMb;
    private ?string $certificateFilePath;
    private ?string $certificatePrivateKeyFilePath;

    public function __construct(
        KernelInterface $kernel,
        private bool $enableSSL,
        ?string $host = null,
        ?string $port = null,
        ?int    $maxConcurrentRequests = null,
        ?int    $maxRequestSizeInMb = null,
        ?string $certificateFilePath = null,
        ?string $certificatePrivateKeyFilePath = null
    )
    {
        if (!$host) {
            if ($kernel->envVarsManager()->has('HOST')) {
                $host = $kernel->envVarsManager()->get('HOST');
                if(!is_string($host)) {
                    throw new \InvalidArgumentException('HOST env var must be a string');
                }
            } else {
                $kernel->logger()->critical("No host was provided, neither through method call or env var 'HOST'");
                die();
            }
        }

        if (!$port) {
            if ($kernel->envVarsManager()->has('PORT')) {
                $port = $kernel->envVarsManager()->get('PORT');
                if(!is_string($port)) {
                    throw new \InvalidArgumentException('PORT env var must be a string');
                }
            } else {
                $kernel->logger()->critical("No port was provided, neither through method call or env var 'PORT'");
                die();
            }
        }

        if (!$maxConcurrentRequests) {
            if ($kernel->envVarsManager()->has('MAX_CONCURRENT_REQUESTS')) {
                $maxConcurrentRequests = $kernel->envVarsManager()->get('MAX_CONCURRENT_REQUESTS');
                if(!is_int($maxConcurrentRequests)) {
                    throw new \InvalidArgumentException('MAX_CONCURRENT_REQUESTS env var must be a string');
                }
            } else {
                $kernel->logger()->critical("No max concurrent requests was provided, neither through method call or env var 'MAX_CONCURRENT_REQUESTS'");
                die();
            }
        }

        if (!$maxRequestSizeInMb) {
            if ($kernel->envVarsManager()->has('MAX_REQUEST_SIZE_IN_MB')) {
                $maxRequestSizeInMb = $kernel->envVarsManager()->get('MAX_REQUEST_SIZE_IN_MB');
                if(!is_int($maxRequestSizeInMb)) {
                    throw new \InvalidArgumentException('MAX_REQUEST_SIZE_IN_MB env var must be a string');
                }
            } else {
                $kernel->logger()->critical("No max request size in mb was provided, neither through method call or env var 'MAX_REQUEST_SIZE_IN_MB'");
                die();
            }
        }

        if (!$certificateFilePath && $this->enableSSL) {
            if ($kernel->envVarsManager()->has('CERTIFICATE_FILE_PATH')) {
                $certificateFilePath = $kernel->envVarsManager()->get('CERTIFICATE_FILE_PATH');
                if(!is_string($certificateFilePath)) {
                    throw new \InvalidArgumentException('CERTIFICATE_FILE_PATH env var must be a string');
                }
            } else {
                $kernel->logger()->critical("You enabled ssl but no certificate file path was provided, neither through method call or env var 'CERTIFICATE_FILE_PATH'");
                die();
            }
        }

        if (!$certificatePrivateKeyFilePath && $this->enableSSL) {
            if ($kernel->envVarsManager()->has('CERTIFICATE_PRIVATE_KEY_FILE_PATH')) {
                $certificatePrivateKeyFilePath = $kernel->envVarsManager()->get('CERTIFICATE_PRIVATE_KEY_FILE_PATH');
                if(!is_string($certificatePrivateKeyFilePath)) {
                    throw new \InvalidArgumentException('CERTIFICATE_PRIVATE_KEY_FILE_PATH env var must be a string');
                }
            } else {
                $kernel->logger()->critical("You enabled ssl but no certificate private key file path was provided, neither through method call or env var 'CERTIFICATE_PRIVATE_KEY_FILE_PATH'");
                die();
            }
        }

        $this->host = $host;
        $this->port = $port;
        $this->maxConcurrentRequests = $maxConcurrentRequests;
        $this->maxRequestSizeInMb = $maxRequestSizeInMb;
        $this->certificateFilePath = $certificateFilePath;
        $this->certificatePrivateKeyFilePath = $certificatePrivateKeyFilePath;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): string
    {
        return $this->port;
    }

    public function maxConcurrentRequests(): int
    {
        return $this->maxConcurrentRequests;
    }

    public function maxRequestSizeInMb(): int
    {
        return $this->maxRequestSizeInMb;
    }

    public function enableSSL(): bool
    {
        return $this->enableSSL;
    }
    
    public function certificateFilePath(): ?string
    {
        return $this->certificateFilePath;
    }
    
    public function certificatePrivateKeyFilePath(): ?string
    {
        return $this->certificatePrivateKeyFilePath;
    }
}