<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure;

use SaaSFormation\Framework\Contracts\Infrastructure\EnvVarsManagerInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\EnvVarsManagerProviderInterface;
use SaaSFormation\Framework\EnvVarsManager\Infrastructure\EnvVarsManager;

class EnvVarsManagerProvider implements EnvVarsManagerProviderInterface
{
    public function __construct(private string $envVarsConfigFilePath)
    {
    }

    public function provide(): EnvVarsManagerInterface
    {
        return new EnvVarsManager($this->envVarsConfigFilePath);
    }
}