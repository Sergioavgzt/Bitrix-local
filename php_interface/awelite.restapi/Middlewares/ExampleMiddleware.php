<?php

namespace Custom\RestApi\Middlewares;

use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\HttpRequest;
use Awelite\RestApi\Bootstrap\Middleware\Middleware;

class ExampleMiddleware implements Middleware
{
    public function handle(HttpRequest $request, callable $next): Json
    {
        return $next($request);
    }
}