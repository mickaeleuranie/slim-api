<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api;

use api\components\CacheComponent;
use api\models\ApiKey;
use api\models\User;
use api\base\Security;

class BaseApi
{
    /* app */
    public static $app;

    /* DI container */
    public static $container;

    /* request */
    public static $request;

    /* db */
    public static $db;

    /* pdo */
    public static $pdo;

    /* elaseticsearch */
    public static $es;

    /* db cache */
    public static $dbcache;

    /* db logger */
    public static $dblogger;

    /* translator cache */
    public static $translator;

    /* user */
    public static $user;

    /* security */
    public static $security;

    /* locale */
    public static $locale;

    /* authorized keys */
    public static $authorizedKeys;

    /* api key */
    public static $apiKey;

    /* api token */
    public static $apiToken;

    public static function init($app)
    {
        static::$app = $app;
        static::$container = $app->getContainer();
        if (!empty(static::$container['db'])) {
            static::$db = static::$container['db'];
        }
        if (!empty(static::$container['request'])) {
            static::$request = static::$container['request'];
        }
        if (!empty(static::$container['pdo'])) {
            static::$pdo = static::$container['pdo'];
        }
        if (!empty(static::$container['es'])) {
            static::$es = static::$container['es'];
        }
        if (!empty(static::$container['dbcache'])) {
            static::$dbcache = static::$container['dbcache'];
        }
        if (!empty(static::$container['dblogger'])) {
            static::$dblogger = static::$container['dblogger'];
        }
        if (!empty(static::$container['translator'])) {
            static::$translator = static::$container['translator'];
        }

        // add security class
        static::$security = new Security;

        // get authorized keys from database if exists
        // else get from settings
        $authorizedKeys = CacheComponent::get('authorizedItem');
        if (!empty($authorizedKeys)) {
            static::$authorizedKeys = (array) $authorizedKeys;
        } else {
            $authorizedItem = CacheComponent::getItem(['authorizedItem']);
            $authorizedKeysTemp = ApiKey::select('slug', 'key')->get()->keyBy('key')->toArray();
            // format authorized keys array
            foreach ($authorizedKeysTemp as $key => $apiKey) {
                $authorizedKeys[$key] = $apiKey['slug'];
            }
            CacheComponent::save($authorizedItem, $authorizedKeys);
            static::$authorizedKeys = $authorizedKeys;
        }

        // replace authorized keys in settings with thoses if not empty
        if (!empty($authorizedKeys)) {
            static::$container->settings['authorizedKeys'] = static::$authorizedKeys;
        }
    }

    /**
     * Set actual user
     * @param User
     */
    public static function setUser(User $user)
    {
        static::$user = $user;
    }

    /**
     * Set short form of locale from translator
     * Transforms en_US to en, fr_FR to fr, etc
     * @param string $locale
     */
    public static function setLocale($locale)
    {
        static::$locale = static::formatLocale($locale);
        Api::$translator->setLocale(static::formatLocale($locale, 'translator'));
    }

    /**
     * Set API key
     * @param string $apiKey
     */
    public static function setApiKey($apiKey)
    {
        static::$apiKey = $apiKey;
    }

    /**
     * Set API token
     * @param string $apiToken
     */
    public static function setApiToken($apiToken)
    {
        static::$apiToken = $apiToken;
    }

    /**
     * Get short form of locale name
     * Transforms en_US to en, fr_FR to fr, etc
     * @param string $locale
     * @param string $type
     */
    public static function formatLocale($locale, $type = 'api')
    {
        if ('translator' === $type) {
            switch ($locale) {
                case 'fr':
                    $locale = 'fr_FR';
                    break;

                case 'en':
                    $locale = 'en_US';
                    break;

                default:
                    $locale = 'en_US';
            }
        } else {
            switch ($locale) {
                case 'fr':
                case 'fr_FR':
                    $locale = 'fr';
                    break;

                case 'en':
                case 'en_US':
                    $locale = 'en';
                    break;

                default:
                    $locale = 'en';
            }
        }
        return $locale;
    }

    /**
     * Translate function
     * @param string $id
     * @param array $parameters
     * @param string $parameters
     * @param string $locale
     */
    public static function t($id, array $parameters = [], $domain = null, $locale = null)
    {
        if (empty($locale)) {
            $locale = static::formatLocale(static::$locale);
        }
        return static::$translator->trans($id, $parameters, $domain, $locale);
    }
}
