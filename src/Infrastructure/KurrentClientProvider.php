<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure;

use GuzzleHttp\Client;
use SaaSFormation\Framework\Contracts\Infrastructure\EnvVarsManagerInterface;

class KurrentClientProvider
{
    public function __construct(private EnvVarsManagerInterface $envVarsManager)
    {
    }

    public function provide(): Client
    {
        return new Client([
            'base_uri' => $this->envVarsManager->get('KURRENT_URI')
        ]);
    }
}