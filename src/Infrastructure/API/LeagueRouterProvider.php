<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\API;

use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;
use Psr\Container\ContainerInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RouterInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RouterProviderInterface;
use SaaSFormation\Framework\Projects\Infrastructure\API\Attributes\Route;
use SaaSFormation\Framework\SharedKernel\UI\HTTP\EndpointInterface;
use SaaSFormation\Framework\SharedKernel\UI\HTTP\MethodEnum;

class LeagueRouterProvider implements RouterProviderInterface
{
    public function provide(ContainerInterface $container): RouterInterface
    {
        $router = new Router();
        $strategy = new ApplicationStrategy();
        $strategy->setContainer($container);
        $router->setStrategy($strategy);

        foreach ($this->getEndpoints() as $class) {
            $reflectedMethod = new \ReflectionMethod($class, 'execute');
            $attributes = $reflectedMethod->getAttributes(Route::class);
            /** @var MethodEnum $method */
            $method = $attributes[0]->getArguments()[0];
            $path = $attributes[0]->getArguments()[1];

            $router->map($method->value, $path, $class . '::doExecute');
        }

        return new LeagueBasedRouter($router);
    }

    /**
     * @return array<int, string>
     * @throws \ReflectionException
     */
    private function getEndpoints(): array
    {
        $classes = get_declared_classes();
        $endpoints = [];

        foreach ($classes as $class) {
            $reflectedClass = new \ReflectionClass($class);
            if(!$reflectedClass->isAbstract() && in_array(EndpointInterface::class, class_implements($class))) {
                $endpoints[] = $class;
            }
        }

        return $endpoints;
    }
}