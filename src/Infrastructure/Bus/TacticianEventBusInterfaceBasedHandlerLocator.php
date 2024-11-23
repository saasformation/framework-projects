<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\Bus;

use League\Tactician\Handler\Locator\HandlerLocator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Application\Bus\EventHandlerInterface;

class TacticianEventBusInterfaceBasedHandlerLocator implements HandlerLocator
{
    /** @var array<string, object> */
    private array $map;

    public function __construct(private readonly ContainerInterface $container, private readonly LoggerInterface $logger)
    {
        $this->map = [];

        $classes = get_declared_classes();

        foreach ($classes as $class) {
            $reflectedClass = new \ReflectionClass($class);
            if(!$reflectedClass->isAbstract() && in_array(EventHandlerInterface::class, class_implements($class))) {
                if($this->container->has($class)) {
                    $service = $this->container->get($class);
                    if(!is_object($service)) {
                        throw new \Exception("Container returned a non object for a known service $class");
                    }
                    $eventName = str_replace('Handler', '', $class);
                    $this->map[$eventName] = $service;
                    $this->logger->debug("Event handler $class for event $eventName has been registered");
                } else {
                    throw new \Exception("Handler service '$class' not found in the container");
                }
            }
        }
    }

    public function getHandlerForCommand($commandName)
    {
        if(!isset($this->map[$commandName])) {
            throw new \Exception("Event handler not found for event '$commandName'");
        }

        return $this->map[$commandName];
    }
}