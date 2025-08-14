<?php

declare(strict_types=1);

namespace Pluswerk\SentryTestExtension\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LastMiddlewareTestExceptionMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (isset($_GET['throw'])) {
            throw new \InvalidArgumentException('This is a test exception from the last middleware.');
        }
        // Do awesome stuff
        return $handler->handle($request);
    }
}
