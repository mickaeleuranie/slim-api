<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\components;

use api\Api;

class CacheComponent
{
    /**
     * Get cached item from given key
     * @param mixed $key Array or string
     * @return mixed
     */
    public static function get($key)
    {
        $item = self::getItem($key);

        if (!$item->isHit() || empty($item->get())) {
            return null;
        }

        // handle json items
        $result = $item->get();
        if (self::isJson($result)) {
            return json_decode($result);
        }
        return $result;
    }

    /**
     * Get item object from cache
     * @param string $key
     * @return Symfony\Component\Cache\CacheItem
     */
    public static function getItem($key)
    {
        return Api::$dbcache->getItem(self::formatKey($key));
    }

    /**
     * Save item in cache
     * @param string $key
     * @return bool
     */
    public static function save($item, $value)
    {
        // handle objects/arrays
        if (is_array($value) || is_object($value)) {
            $item->set(json_encode($value));
        } else {
            $item->set($value);
        }

        return Api::$dbcache->save($item);
    }

    /**
     * Save item in cache
     * @param string $key
     * @return bool
     */
    public static function delete($key)
    {
        return Api::$dbcache->deleteItem(self::formatKey($key));
    }

    /**
     * Format cache key
     * @param string $key
     * @return string
     */
    private static function formatKey($key)
    {
        // @TODO set cache prefix in environment file
        if (is_array($key)) {
            $keyTemp = getenv('ENV') . '_' . CACHE_PREFIX . '_';
            foreach ($key as $p) {
                // don't allow array with more than one dimension
                if (is_array($p)) {
                    throw new ApiException('Only one dimensional arrays are accepted.');
                }
                $keyTemp .= (string) str_replace('\\', '_', $p);
            }
            $key = $keyTemp;
        } else {
            $key = CACHE_PREFIX . '_' . $key;
        }

        return $key;
    }

    /**
     * Check if string is a json object
     * @param string $string
     * @return bool
     */
    private static function isJson($string)
    {
        if (!in_array(mb_substr($string, 0, 1, 'utf-8'), ['[', '{'])) {
            return false;
        }

        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
