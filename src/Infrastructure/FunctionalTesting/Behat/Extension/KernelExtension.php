<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\FunctionalTesting\Behat\Extension;

use Assert\Assert;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RouterProviderInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\ContainerProviderInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\KernelInterface;
use SaaSFormation\Framework\Projects\Infrastructure\API\APIKernel;
use SaaSFormation\Framework\Projects\Infrastructure\API\LeagueRouterProvider;
use SaaSFormation\Framework\Projects\Infrastructure\SymfonyContainerProvider;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class KernelExtension implements Extension
{
    public function process(ContainerBuilder $container): void
    {
    }

    public function getConfigKey(): string
    {
        return "saasformation_framework";
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
        $builderChildren = $builder->children();
        $kernelArrayNode = $builderChildren->arrayNode('kernel');
        $kernelChildren = $kernelArrayNode->children();
        $kernelChildren->scalarNode('main_services_file_path');
        $kernelChildren->scalarNode('class');
    }

    /**
     * @param ContainerBuilder $container
     * @param array<'kernel', array<string, string>> $config
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function load(ContainerBuilder $container, array $config): void
    {
        $kernel = $this->buildKernel($config);

        $frameworkContainer = $kernel->container();

        Assert::that($frameworkContainer)->isInstanceOf(ContainerBuilder::class, "Only symfony container is allow to use this extension");

        foreach ($frameworkContainer->getServiceIds() as $serviceId) {
            $container->set($serviceId, $frameworkContainer->get($serviceId));
        }
    }

    /**
     * @param array<'kernel', array<string, string>> $config
     * @return KernelInterface
     */
    private function buildKernel(array $config): KernelInterface
    {
        return new APIKernel(
            new SymfonyContainerProvider($config['kernel']['main_services_file_path']),
            new LeagueRouterProvider()
        );
    }
}