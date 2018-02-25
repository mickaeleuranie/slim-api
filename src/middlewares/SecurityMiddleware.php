<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\middlewares;

use api\Api;

class SecurityMiddleware
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Security middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        Api::check($request, $response, $this->container);
        $response = $next($request, $response);

        return $response;
    }
}
