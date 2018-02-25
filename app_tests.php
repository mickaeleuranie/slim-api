<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

date_default_timezone_set('UTC');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// require_once __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$app = new \Slim\App(['settings' => require __DIR__ . '/src/config/settings.php']);

require_once __DIR__ . '/src/config/dependencies.php';

// specific dependencies for test
$container = $app->getContainer();

/** setup Eloquent (test) */
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db_test']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$container['db'] = function ($c) use ($capsule) {
    return $capsule;
};

/** setup PDO (test) **/
$container['pdo'] = function ($container) {
    $pdo = new \Slim\PDO\Database(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME_TEST') . ';charset=' . getenv('DB_CHARSET'),
        getenv('DB_USER'),
        getenv('DB_PASSWORD')
    );
    // $pdo = new \PDO('sqlite:' . __DIR__ . '/tests/temp/db.sqlite3');
    // $pdo = new \PDO('sqlite::memory:');
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
    return $pdo;
};

require_once __DIR__ . '/src/config/handlers.php';
require_once __DIR__ . '/src/config/middleware.php';

// routes
require __DIR__ . '/src/routes/autoload.php';

// set globally accessible Api class
require_once __DIR__ . '/src/BaseApi.php';
require_once __DIR__ . '/src/Api.php';
\api\Api::init($app);

return $app;
