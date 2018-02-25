<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

$settings = [
    'displayErrorDetails' => getenv('APP_DEBUG', false),
    'db' => [
        'driver'      => getenv('DB_DRIVER'),
        'host'        => getenv('DB_HOST'),
        'database'    => getenv('DB_NAME'),
        'username'    => getenv('DB_USER'),
        'password'    => getenv('DB_PASSWORD'),
        'charset'     => getenv('DB_CHARSET'),
        'collation'   => getenv('DB_COLLATION'),
        'prefix'      => getenv('DB_PREFIX'),
        'unix_socket' => getenv('DB_SOCKET', ''),
        'modes'     => [
            'STRICT_TRANS_TABLES',
            // 'NO_ZERO_IN_DATE',
            // 'NO_ZERO_DATE',
            'ERROR_FOR_DIVISION_BY_ZERO',
            'NO_AUTO_CREATE_USER',
            'NO_ENGINE_SUBSTITUTION',
        ]
    ],
    'db_test' => [
        'driver'    => getenv('DB_DRIVER'),
        'host'      => getenv('DB_HOST'),
        'database'  => getenv('DB_NAME_TEST'),
        'username'  => getenv('DB_USER'),
        'password'  => getenv('DB_PASSWORD'),
        'charset'   => getenv('DB_CHARSET'),
        'collation' => getenv('DB_COLLATION'),
        'prefix'    => getenv('DB_PREFIX'),
        'modes'     => [
            'STRICT_TRANS_TABLES',
            // 'NO_ZERO_IN_DATE',
            // 'NO_ZERO_DATE',
            'ERROR_FOR_DIVISION_BY_ZERO',
            'NO_AUTO_CREATE_USER',
            'NO_ENGINE_SUBSTITUTION',
        ]
    ],
    'log' => [
        'name'  => getenv('LOG_NAME', 'slim'),
        'file'  => getenv('LOG_FILE', 'logs/slim.log'),
        'level' => getenv('LOG_LEVEL', 300),
    ],
    // use this entry to authorize some hard-coded keys
    'authorizedKeys' => [
    //     'YOUR_KEY' => 'slug',
    ],
    'rememberMeDuration' => 3600 * 24 * 30, // login - remember me duration, 30 days
    'adminEmail' => getenv('EMAIL_ADMIN_CONTACT'), // this is used in contact page
    'signup' => [
        'activeAfterSignup' => true,
        'hybridauthUsernamePrefix' => 'slimapi_auth_',
        'hybridauthPasswordPrefix' => 'slimapi_s&krvFYsT9mb43e$2jX',
        'hybridauthEmailPostfix'   => '@' . getenv('API_DOMAIN_NAME'),
    ],
    'account' => [
        'passwordResetValidityDuration'  => 15, // in minutes
        'emailUpdateValidityDuration'    => 15, // in minutes
        'accountConfirmValidityDuration' => 48, // in hours
    ],
    'errors' => [
        'sendMail' => [
            '500' => false,
        ]
    ],
    'email' => [
        'gmail' => [
            'username' => getenv('GMAIL_USERNAME'),
            'password' => getenv('GMAIL_PASSWORD'),
        ],
    ],
    // to limit request for big data
    // to use with createCommand, not find*()
    'request' => [
        'limit' => 5000,
    ],
    'url' => getenv('API_PROTOVOL') . '://' . getenv('API_DOMAIN_NAME'),
    'api' => [
        'client_secret' => getenv('API_SECRET') // default client secret
    ],
    'availableLanguages' => [
        'en_US',
        'en',
        'fr_FR',
        'fr',
    ],
    'defaultLanguage' => 'en_US',
];

// add cache to routes when in production
// ex : __DIR__ . '/../routes.cache.php',
if (getenv('ROUTER_CACHE_FILE', null)) {
    $settings['routerCacheFile'] = getenv('ROUTER_CACHE_FILE');
}

// set cache key's prefix
defined('CACHE_PREFIX') || define('CACHE_PREFIX', 'api_cache_');

return $settings;
