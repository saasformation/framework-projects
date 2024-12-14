<?php

namespace SaaSFormation\Framework\Projects\Infrastructure;

class Session
{
    public function __construct(public string $sessionId, public \MongoDB\Driver\Session $mongoDBSession)
    {
    }
}