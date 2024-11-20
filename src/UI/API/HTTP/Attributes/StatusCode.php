<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\UI\API\HTTP\Attributes;

use Attribute;
use SaaSFormation\Framework\Contracts\UI\HTTP\StatusEnum;

#[Attribute]
class StatusCode
{
    public function __construct(public StatusEnum $status)
    {
    }
}