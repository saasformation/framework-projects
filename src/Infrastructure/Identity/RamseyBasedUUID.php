<?php

namespace SaaSFormation\Framework\Projects\Infrastructure\Identity;

use Ramsey\Uuid\UuidInterface as RamseyUuidInterface;
use SaaSFormation\Framework\SharedKernel\Common\Identity\UUIDInterface;

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