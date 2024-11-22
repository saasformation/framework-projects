<?php

namespace SaaSFormation\Framework\Projects\Infrastructure\Identity;

use Ramsey\Uuid\Uuid;
use SaaSFormation\Framework\Contracts\Common\Identity\UUIDFactoryInterface;
use SaaSFormation\Framework\Contracts\Common\Identity\UUIDInterface;

class RamseyBasedUUIDFactory implements UuidFactoryInterface
{
    /** @var array|int[] */
    public const array ALLOWED_VERSIONS = [
        1,4,7
    ];

    public function generate(int $version = 7): UUIDInterface
    {
        $this->checkVersionIsAllowed($version);

        return match ($version) {
            1 => new RamseyBasedUUID(Uuid::uuid1()),
            4 => new RamseyBasedUUID(Uuid::uuid4()),
            default => new RamseyBasedUUID(Uuid::uuid7()),
        };
    }

    public function fromString(string $uuid): UUIDInterface
    {
        return new RamseyBasedUUID(Uuid::fromString($uuid));
    }

    /**
     * @param int $version
     * @return void
     * @throws \Exception
     */
    public function checkVersionIsAllowed(int $version): void
    {
        if (!in_array($version, self::ALLOWED_VERSIONS)) {
            $allowedVersionsString = implode(',', self::ALLOWED_VERSIONS);
            throw new \Exception("Invalid version provided, only '$allowedVersionsString' versions allowed.");
        }
    }
}