<?php

// // $settings = require __DIR__ . '/src/config/settings.php';
// require __DIR__ . '/app.php';


// global $app;
// var_dump($app);die;
// $pdo = $app->getDatabase()->getPdo();


require __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$pdo = new \PDO('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME_TEST') . ';charset=' . getenv('DB_CHARSET'), getenv('DB_USER'), getenv('DB_PASSWORD'));
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds'      => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_database' => 'development',
        'development'      => [
           'name'       => getenv('DB_NAME_TEST'),
           'connection' => $pdo
        ]
    ],
    'version_order' => 'creation',
];
