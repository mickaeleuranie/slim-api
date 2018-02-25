<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

if ('cli' !== PHP_SAPI) {
    print('error: must be executed from CLI');
    exit(1);
}

date_default_timezone_set('UTC');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$settings = require __DIR__ . '/src/config/settings.php';
$app = new \Slim\App(['settings' => $settings]);

require_once __DIR__ . '/src/config/dependencies.php';

// specific dependencies for test
$container = $app->getContainer();

$container['errorHandler'] = function ($container) {
    return function ($request, $response, $exception) use ($container) {
        print('error : ' . $exception->getMessage());
        exit(1);
    };
};
$container['notFoundHandler'] = function ($container) {
    return function ($request, $response) use ($container) {
        print('not_found');
        exit(1);
    };
};

// add possible commands
$container['commands'] = function ($container) {
    // @TODO generate list of commands from files in tasks folder
    // or from classes functions
    return [
        // recipe tasks
        'recipe'                             => \tasks\RecipeTask::class,
        'recipe/generateingredientsanalysis' => \tasks\RecipeTask::class,
        'recipe/generaterecipesanalysis'     => \tasks\RecipeTask::class,
        'recipe/importToES'                  => \tasks\RecipeTask::class,
    ];
};

// add task middleware
// $app->add(\adrianfalleiro\SlimCLIRunner::class);
$app->add(\api\middlewares\CliMiddleware::class);

// load tasks
$apiHelper = new \api\helpers\ApiHelper;
$apiHelper->includeFilesFrom(__DIR__ . '/tasks/', 'Task.php');
// require __DIR__ . '/tasks/autoload.php';

// set globally accessible Api class
require_once __DIR__ . '/src/BaseApi.php';
require_once __DIR__ . '/src/Api.php';
\api\Api::init($app);

$app->run();
