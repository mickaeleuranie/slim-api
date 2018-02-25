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

class ErrorHandler
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(Request $request, Response $response, \Exception $exception)
    {
        ErrorComponent::getRequestDetails($request);

        $body = [
            'message' => $exception->getMessage() ? $exception->getMessage() : 'Something went wrong!',
            'code'    => $exception->getCode() ? $exception->getCode() : '500',
        ];

        // add trace
        if (getenv('ENV') !== 'production' && !empty($exception)) {
            $body['trace'] = $exception->getTrace()[0];
        }

        ErrorComponent::handle($request, $exception);

        return $response
            ->withStatus($body['code'])
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($body, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
}
