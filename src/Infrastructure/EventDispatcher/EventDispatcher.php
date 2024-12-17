<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\EventDispatcher;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\SharedKernel\Application\EventDispatcher\EventDispatcherInterface;
use SaaSFormation\Framework\SharedKernel\Application\EventDispatcher\ListenerInterface;
use SaaSFormation\Framework\SharedKernel\Domain\Messages\AbstractDomainEvent;
use SaaSFormation\Framework\SharedKernel\Domain\Messages\DomainEventInterface;

class EventDispatcher implements EventDispatcherInterface
{
    /** @var array<string, array<ListenerInterface>> */
    private array $map;

    public function __construct(private readonly ContainerInterface $container, private readonly LoggerInterface $logger)
    {
        $this->map = [];

        $classes = get_declared_classes();

        foreach ($classes as $class) {
            $reflectedClass = new \ReflectionClass($class);
            if(!$reflectedClass->isAbstract() && in_array(ListenerInterface::class, class_implements($class))) {
                if($this->container->has($class)) {
                    $service = $this->container->get($class);
                    if(!$service instanceof ListenerInterface) {
                        throw new \Exception("Event handler must implement ListenerInterface: $class doest not");
                    }
                    $method = new \ReflectionMethod($class, 'listen');
                    $args = $method->getParameters();
                    if(count($args) === 1) {
                        $event = $args[0];
                        if($event->getType() instanceof \ReflectionUnionType || $event->getType() instanceof \ReflectionIntersectionType) {
                            $types = $event->getType()->getTypes();
                            foreach($types as $type) {
                                if($type instanceof \ReflectionNamedType && !in_array($type->getName(), [AbstractDomainEvent::class, DomainEventInterface::class])) {
                                    if(class_exists($type->getName())) {
                                        $reflectedType = new \ReflectionClass($type->getName());
                                        if($reflectedType->getParentClass() && $reflectedType->getParentClass()->getName() === AbstractDomainEvent::class) {
                                            $code = $type->getName()::getDomainEventCode();
                                            if(is_string($code)) {
                                                $this->map[$code][] = $service;
                                                $this->logger->debug("Event handler $class for event with code $code has been registered");
                                            }
                                        }
                                    } else {
                                        throw new \Exception("Event type not found: " . $type->getName());
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

    public function dispatch(DomainEventInterface $event): void
    {
        if(isset($this->map[$event->getDomainEventCode()])) {
            foreach($this->map[$event->getDomainEventCode()] as $handler) {
                $handler->listen($event);
            }
        }
    }
}