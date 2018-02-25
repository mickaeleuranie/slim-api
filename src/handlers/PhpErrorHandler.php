<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\handlers;

use api\Api;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use api\components\ErrorComponent;
use api\components\EmailComponent;
use Slim\Handlers\PhpError;

final class PhpErrorHandler extends PhpError
{
    protected $logger;

    public function __construct()
    {
        $this->logger = Api::$container->logger;
    }

    public function __invoke(Request $request, Response $response, \Throwable $error)
    {
        $log = $error->getMessage() . '<br>';
        if (!empty($error->getTrace())) {
            $log .= json_encode($error->getTrace()[0], JSON_PRETTY_PRINT) . '<br>';
        }
        $log .= ErrorComponent::getRequestDetails($request);

        $this->logger->critical($log);

        $body = [
            'message' => Api::t('error.internal_server_error'),
            'status'  => $error->getCode() ? $error->getCode() : 500,
        ];

        // add trace
        if (getenv('ENV') !== 'production' && !empty($error)) {
            $body['message'] = Api::t('error.internal_server_error') . ' : ' . $error->getMessage();
            $body['trace'] = $error->getTrace()[0];
        }

        ErrorComponent::handle($request, $error);

        return $response
                ->withStatus($body['status'])
                ->withHeader("Content-type", "application/problem+json")
                ->write(json_encode($body, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
}
