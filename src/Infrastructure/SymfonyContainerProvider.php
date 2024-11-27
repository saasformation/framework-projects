<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure;

use Exception;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\ContainerProviderInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\EnvVarsManagerInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\KernelInterface;
use SaaSFormation\Framework\Projects\Infrastructure\API\APIKernel;
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
    public function provide(KernelInterface $kernel): ContainerInterface
    {
        $container = new ContainerBuilder();

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__));
        $loader->load($this->servicesFilePath);

        $container->setDefinition(KernelInterface::class, (new Definition(KernelInterface::class, []))->setSynthetic(true)->setPublic(true));

        $container->compile();

        $container->set(KernelInterface::class, $kernel);

        return $container;
    }
}