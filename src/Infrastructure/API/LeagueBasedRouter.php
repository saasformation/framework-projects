<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\Infrastructure\API;

use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SaaSFormation\Framework\Contracts\Infrastructure\API\RouterInterface;

readonly class LeagueBasedRouter implements RouterInterface
{
    public function __construct(private Router $router)
    {
    }

    public function route(ServerRequestInterface $request): ResponseInterface
    {
        return $this->router->dispatch($request);
    }
}