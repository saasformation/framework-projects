<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\EventDispatcher;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Application\Bus\EventHandlerInterface;
use SaaSFormation\Framework\Contracts\Application\EventDispatcher\EventDispatcherInterface;
use SaaSFormation\Framework\Contracts\Domain\DomainEvent;

class EventDispatcher implements EventDispatcherInterface
{
    /** @var array<string, array<EventHandlerInterface>> */
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
                    if(!$service instanceof EventHandlerInterface) {
                        throw new \Exception("Event handler must implement EventHandlerInterface: $class doest not");
                    }
                    $method = new \ReflectionMethod($class, 'handle');
                    $args = $method->getParameters();
                    if(count($args) === 1) {
                        $event = $args[0];
                        if($event->getType() instanceof \ReflectionUnionType || $event->getType() instanceof \ReflectionIntersectionType) {
                            $types = $event->getType()->getTypes();
                            foreach($types as $type) {
                                if($type instanceof \ReflectionNamedType && $type->getName() !== DomainEvent::class) {
                                    $reflectedType = new \ReflectionClass($type);
                                    if($reflectedType->getParentClass() && $reflectedType->getParentClass()->getName() === DomainEvent::class) {
                                        $code = $type->getName()::code();
                                        if(is_string($code)) {
                                            $this->map[$code][] = $service;
                                            $this->logger->debug("Event handler $class for event with code $code has been registered");
                                        }
                                    }
                                }
                            }
                        } else {
                            throw new \Exception("Event handler method param must be typed (DomainEvent child)");
                        }
                    }
                } else {
                    throw new \Exception("Handler service '$class' not found in the container");
                }
            }
        }
    }

    public function dispatch(DomainEvent $event): void
    {
        if(isset($this->map[$event->code()])) {
            foreach($this->map[$event->code()] as $handler) {
                $handler->handle($event);
            }
        }
    }
}