<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\API\Attributes;

use Attribute;
use SaaSFormation\Framework\Contracts\UI\HTTP\MethodEnum;

#[Attribute]
class Route
{
    public function __construct(public MethodEnum $method, public string $path)
    {
    }
}