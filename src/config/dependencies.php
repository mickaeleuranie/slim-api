<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

$container = $app->getContainer();

/** setup localisation **/
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\PhpFileLoader;

$translator = new Translator($container['settings']['defaultLanguage'], new MessageSelector());
// php loader
$translator->addLoader('php', new PhpFileLoader());
// locales
$translator->addResource('php', __DIR__ . '/../i18n/en_US.php', 'en_US'); // english
$translator->addResource('php', __DIR__ . '/../i18n/fr_FR.php', 'fr_FR'); // french
// fallback locale
$translator->setFallbackLocales([$container['settings']['defaultLanguage']]);
$container['translator'] = function ($c) use ($translator) {
    return $translator;
};

/** setup Eloquent */
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$container['db'] = function ($c) use ($capsule) {
    return $capsule;
};

/** setup PDO **/
$container['pdo'] = function ($c) {

    // format DSN
    if ('production' === getenv('ENV') && !empty(getenv('DB_CONNECTION_NAME'))) {
        $dsn = 'mysql:unix_socket=/cloudsql/' . getenv('DB_CONNECTION_NAME');
    } else {
        $dsn = 'mysql:host=' . getenv('DB_HOST');
    }

    // add common DSN values
    $dsn .= ';dbname=' . getenv('DB_NAME') . ';charset=' . getenv('DB_CHARSET');

    $pdo = new \Slim\PDO\Database(
        $dsn,
        getenv('DB_USER'),
        getenv('DB_PASSWORD')
    );
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
    return $pdo;
};

/** setup DB cache **/
// activate only in production
// if ('production' === getenv('ENV'))
$container['dbcache'] = function ($c) {
    $config = [
        'schema' => 'tcp',
        'host'   => 'localhost',
        'port'   => getenv('REDIS_PORT'),
        // other options
    ];
    $connection = new Predis\Client($config);
    return new Symfony\Component\Cache\Adapter\RedisAdapter($connection);
};
// }

/** setup logger */
$container['logger'] = function ($c) {
    $logger = new \Monolog\Logger('slim');

    $formatter = new \Monolog\Formatter\LineFormatter(
        '[%datetime%] [%level_name%]: %message% %context%' . "\n",
        null,
        true,
        true
    );

    /* Log to timestamped files */
    $rotating = new \Monolog\Handler\RotatingFileHandler(__DIR__ . '/../../logs/slim.log', 0, \Monolog\Logger::DEBUG);
    $rotating->setFormatter($formatter);
    $logger->pushHandler($rotating);

    return $logger;
};

/** setup db logger **/
$container['dblogger'] = function ($c) {
    $dbLogger = new \api\base\DbLogger();
    return $dbLogger;
};

/** setup view (twig) **/
$container['twig'] = function ($c) {
    $view = new \Slim\Views\Twig(__DIR__ . '/../templates/', [
        'cache' => __DIR__ . '/../../cache'
    ]);

    // instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new \Slim\Views\TwigExtension($c['router'], $basePath));

    return $view;
};

/** setup view (plates) **/
$container['view'] = function ($c) {
    $view = new \Projek\Slim\Plates([
        // Path to view directory (default: null)
        'directory' => __DIR__ . '/../templates',
        // Path to asset directory (default: null)
        'assetPath' => __DIR__ . '/../../public/assets',
        // Template extension (default: 'php')
        // 'fileExtension' => 'tpl',
        // Template extension (default: false) see: http://platesphp.com/extensions/asset/
        'timestampInFilename' => false,
    ]);

    // Set \Psr\Http\Message\ResponseInterface object
    // Or you can optionaly pass `$c->get('response')` in `__construct` second parameter
    $view->setResponse($c->get('response'));

    // Instantiate and add Slim specific extension
    $view->loadExtension(new Projek\Slim\PlatesExtension(
        $c->get('router'),
        $c->get('request')->getUri()
    ));

    return $view;
};

/** setup elasticsearch client **/
use Elasticsearch\ClientBuilder;

$container['es'] = function ($c) {
    $hosts = [
        [
            'host'   => 'localhost',
            'port'   => getenv('ES_PORT'),
            // 'scheme' => 'https',
            'user'   => getenv('ES_USER'),
            'pass'   => getenv('ES_PASSWORD'),
        ],
    ];

    $client = ClientBuilder::create()
        ->setLogger($c['logger'])
        ->setHosts($hosts)
        ->build()
    ;

    return $client;
};

// custom requires

// main helper
require_once __DIR__ . '/../helpers/ApiHelper.php';
$apiHelper = new \api\helpers\ApiHelper;

// helpers
$apiHelper->includeFilesFrom(__DIR__ . '/../helpers/', null, ['ApiHelper.php']);

// exceptions
$apiHelper->includeFilesFrom(__DIR__ . '/../exceptions/');

// base
$apiHelper->includeFilesFrom(__DIR__ . '/../base/');

// filters
$apiHelper->includeFilesFrom(__DIR__ . '/../filters/');

// components
$apiHelper->includeFilesFrom(__DIR__ . '/../components/');

// controllers
$apiHelper->includeFilesFrom(__DIR__ . '/../controllers/', 'Controller.php');

// models
$apiHelper->includeFilesFrom(__DIR__ . '/../models/', 'Db.php');

// middlewares
$apiHelper->includeFilesFrom(__DIR__ . '/../middlewares/');
