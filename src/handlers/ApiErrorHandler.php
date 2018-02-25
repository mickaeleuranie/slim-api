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

final class ApiErrorHandler extends Error
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(Request $request, Response $response, \Exception $exception)
    {
        $log = $exception->getMessage() . '<br>';
        if (!empty($exception->getTrace())) {
            $log .= json_encode($exception->getTrace()[0], JSON_PRETTY_PRINT) . '<br>';
        }
        $log .= ErrorComponent::getRequestDetails($request);

        $this->logger->critical($log);

        $body = [
            'message' => $exception->getMessage(),
            'status'  => $exception->getCode() ? $exception->getCode() : 500,
        ];

        // add trace
        if (getenv('ENV') !== 'production' && !empty($exception)) {
            $body['trace'] = $exception->getTrace()[0];
        }

        ErrorComponent::handle($request, $exception);

        // handle not Slim handled exception
        try {
            return $response
                    ->withStatus($body['status'])
                    ->withHeader("Content-type", "application/problem+json")
                    ->write(json_encode($body, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // InvalidArgumentException
        } catch (\InvalidArgumentException $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            die;
        }
    }
}
