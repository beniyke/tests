<?php

declare(strict_types=1);

namespace Tests\Fixtures\Middleware;

use Closure;
use Core\Middleware\MiddlewareInterface;
use Helpers\Http\Request;
use Helpers\Http\Response;

class SecondDummyMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Response $response, Closure $next): mixed
    {
        $response = $next($request, $response);
        if ($response instanceof Response) {
            $response->header(['X-Second-Middleware' => 'passed']);
        }

        return $response;
    }
}
