<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure;

use Exception;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\ContainerProviderInterface;
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
    public function provide(LoggerInterface $logger): ContainerInterface
    {
        $container = new ContainerBuilder();

        $container->set('default_logger', $logger);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__));
        $loader->load($this->servicesFilePath);

        $container->setDefinition(Logger::class, (new Definition(Logger::class))->setSynthetic(true));
        $container->setAlias('default_logger', Logger::class);
        $container->setAlias(LoggerInterface::class, 'default_logger');

        $container->compile();

        $container->set(Logger::class, $logger);

        return $container;
    }
}