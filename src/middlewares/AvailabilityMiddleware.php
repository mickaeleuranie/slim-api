<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\middlewares;

use api\Api;

class AvailabilityMiddleware
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
        $checkAvailability = Api::checkAvailability();
        if (!empty($checkAvailability)) {
            return $response->withStatus(503)
                            ->withJson($checkAvailability);
        }
        $response = $next($request, $response);

        return $response;
    }
}
