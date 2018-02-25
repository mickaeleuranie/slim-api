<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\handlers;

use api\components\ErrorComponent;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Handlers\Error;

final class NotFoundHandler extends Error
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(Request $request, Response $response, \Exception $exception = null)
    {
        $body = [
            'message' => ($exception && $exception->getMessage()) ? $exception->getMessage() : 'Not found',
            'status'  => '404',
        ];

        // add trace
        if (getenv('ENV') !== 'production' && !empty($exception)) {
            $body['trace'] = $exception->getTrace()[0];
        }

        ErrorComponent::getRequestDetails($request);

        return $response
            ->withStatus(404)
            ->withHeader("Content-type", "application/problem+json")
            ->write(json_encode($body, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
}
