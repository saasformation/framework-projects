<?php

namespace SaaSFormation\Framework\Projects\Infrastructure\Identity;

use SaaSFormation\Framework\Contracts\Common\Identity\UUIDInterface;
use Ramsey\Uuid\UuidInterface as RamseyUuidInterface;

readonly class RamseyBasedUUID implements UUIDInterface
{
    public function __construct(private RamseyUuidInterface $id)
    {
    }

    public function humanReadable(): string
    {
        return $this->id->toString();
    }
}