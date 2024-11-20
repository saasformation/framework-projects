<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\ContainerProviderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SymfonyContainerProvider implements ContainerProviderInterface
{
    public function __construct(private string $servicesFilePath)
    {
    }

    /**
     * @throws Exception
     */
    public function provide(LoggerInterface $logger): ContainerInterface
    {
        $container = new ContainerBuilder();

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__));
        $loader->load($this->servicesFilePath);

        $container->set('default_logger', $logger);

        $container->compile();

        return $container;
    }
}