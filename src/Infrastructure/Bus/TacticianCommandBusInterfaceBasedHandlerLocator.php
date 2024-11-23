<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\Bus;

use League\Tactician\Handler\Locator\HandlerLocator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Application\Bus\CommandHandlerInterface;

class TacticianCommandBusInterfaceBasedHandlerLocator implements HandlerLocator
{
    /** @var array<string, object> */
    private array $map;

    public function __construct(private readonly ContainerInterface $container, private readonly LoggerInterface $logger)
    {
        $this->map = [];

        $classes = get_declared_classes();

        foreach ($classes as $class) {
            $reflectedClass = new \ReflectionClass($class);
            if(!$reflectedClass->isAbstract() && in_array(CommandHandlerInterface::class, class_implements($class))) {
                if($this->container->has($class)) {
                    $service = $this->container->get($class);
                    if(!is_object($service)) {
                        throw new \Exception("Container returned a non object for a known service $class");
                    }
                    $commandName = str_replace('Handler', '', $class);
                    $this->map[$commandName] = $service;
                    $this->logger->debug("Command handler $class for command $commandName has been registered");
                } else {
                    throw new \Exception("Handler service '$class' not found in the container");
                }
            }
        }
    }

    public function getHandlerForCommand($commandName)
    {
        if(!isset($this->map[$commandName])) {
            throw new \Exception("Command handler not found for command '$commandName'");
        }

        return $this->map[$commandName];
    }
}