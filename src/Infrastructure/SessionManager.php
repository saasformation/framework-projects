<?php

namespace SaaSFormation\Framework\Projects\Infrastructure;

use MongoDB\Client;
use Ramsey\Uuid\Uuid;

class SessionManager
{
    /** @var Session[] */
    private array $sessions;

    public function __construct(private readonly Client $mongoDBClient)
    {
    }

    public function startSession(): string
    {
        $sessionId = Uuid::uuid7()->toString();

        $this->sessions[$sessionId] = new Session($sessionId, $this->mongoDBClient->startSession());

        return $sessionId;
    }

    public function getSession(string $sessionId): Session
    {
        return $this->sessions[$sessionId];
    }
}