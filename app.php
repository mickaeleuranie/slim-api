<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

date_default_timezone_set('UTC');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$app = new \Slim\App(['settings' => require __DIR__ . '/src/config/settings.php']);

require __DIR__ . '/src/config/dependencies.php';
require __DIR__ . '/src/config/handlers.php';
require __DIR__ . '/src/config/middleware.php';

// default API view
$app->get('/', function ($request, $response, $arguments) {
    return $this->twig->render($response, 'index.phtml');
});
$app->get('/v1', function ($request, $response, $arguments) {
    return $this->twig->render(
        $response,
        'index.phtml',
        [
            'version' => 1
        ]
    );
});

// routes
require __DIR__ . '/src/routes/autoload.php';

// set globally accessible Api class
require __DIR__ . '/src/BaseApi.php';
require __DIR__ . '/src/Api.php';
\api\Api::init($app);

$app->run();
