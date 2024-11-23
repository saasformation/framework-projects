<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\Bus;

use Exception;
use League\Tactician\Logger\Formatter\Formatter;
use Psr\Log\LoggerInterface;
use SaaSFormation\Framework\Contracts\Domain\DomainEvent;

class TacticianEventBusLogFormatter implements Formatter
{
    public function logCommandReceived(LoggerInterface $logger, $command)
    {
        if(!$command instanceof DomainEvent) {
            throw new Exception('Event must be instance of DomainEvent');
        }

        $logger->debug("Event {$command->code()} received");
    }

    public function logCommandSucceeded(LoggerInterface $logger, $command, $returnValue)
    {
        if(!$command instanceof DomainEvent) {
            throw new Exception('Event must be instance of DomainEvent');
        }

        $logger->debug("Event {$command->code()} processed");
    }

    public function logCommandFailed(LoggerInterface $logger, $command, Exception $e)
    {
        if(!$command instanceof DomainEvent) {
            throw new Exception('Event must be instance of DomainEvent');
        }

        $logger->error("Event {$command->code()} processing failed", [
            'error' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ]
        ]);
    }
}