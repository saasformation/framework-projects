<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure;

use Exception;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\ContainerProviderInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\EnvVarsManagerInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\KernelInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SymfonyContainerProvider implements ContainerProviderInterface
{
    public function __construct(private string $servicesFilePath)
    {
    }

    /**
     * @throws Exception
     */
    public function provide(KernelInterface $kernel, EnvVarsManagerInterface $envVarsManager, LoggerInterface $logger): ContainerInterface
    {
        $container = new ContainerBuilder();

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__));
        $loader->load($this->servicesFilePath);

        $container->setDefinition(KernelInterface::class, (new Definition(KernelInterface::class, []))->setSynthetic(true)->setPublic(true));
        $container->setDefinition(EnvVarsManagerInterface::class, (new Definition(EnvVarsManagerInterface::class, []))->setSynthetic(true)->setPublic(true));
        $container->setDefinition(Logger::class, (new Definition(Logger::class, []))->setSynthetic(true)->setPublic(true));
        $container->setAlias(LoggerInterface::class, Logger::class);
        $container->setAlias('default_logger', LoggerInterface::class);

        $container->compile();

        $container->set(KernelInterface::class, $kernel);
        $container->set(EnvVarsManagerInterface::class, $envVarsManager);
        $container->set(Logger::class, $logger);

        return $container;
    }
}