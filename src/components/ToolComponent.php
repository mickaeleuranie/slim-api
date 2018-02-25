<?php

namespace api\components;

use api\Api;
use api\enums\Enum;
use api\exceptions\ApiException;

class ToolComponent
{

    /**
     * Format time to readable string
     * @param integer $time
     * @return string
     */
    public static function getTimeString($time)
    {
        if ($time < 60) {
            return $time + 'mn';
        }

        $timeString = '';
        $hour = floor($time / 60);
        $minutes = (int) $time - ($hour * 60);

        $timeString .= $hour . 'h';
        if (0 !== (int) $minutes) {
            if (10 > $minutes) {
                $timeString .= '0' . $minutes;
            } else {
                $timeString .= $minutes;
            }
        }
        return $timeString;
    }

    /**
     * Format cache key from given parameter (string or array)
     * @param mixed string|array
     * @throws \api\exeptions\ApiException
     * @return string
     */
    public static function formatCacheKey($param)
    {
        $key = getenv('ENV') . '_' . CACHE_PREFIX;

        if (is_array($param)) {
            foreach ($param as $p) {
                // don't allow array with more than one dimension
                if (is_array($p)) {
                    throw new ApiException('Only one dimensional arrays are accepted.');
                }
                $key .= (string) str_replace('\\', '_', $p);
            }
        } else {
            $key .= (string) $p;
        }
        return $key;
    }

    /**
     * Get age from birthdate
     * @param string $birthdate
     * @return integer
     */
    public static function getAgeFromBirthdate($birthdate)
    {
        if (empty($birthdate)) {
            return 0;
        }
        $now = new \DateTime();
        $birthdateDatetime = new \DateTime($birthdate);
        $interval = $now->diff($birthdateDatetime);
        return $interval->y;
    }

    /**
     * Turns SoMe tExt into some-text, Some text B2 into some-text-b2, Total Fat 17` into total-fat-17
     * @param string
     * @return string
     */
    public static function toSlug($string)
    {
        $string = str_replace('&', 'and', $string);
        $string = strtolower(preg_replace('/\s+|,/', '-', $string));

        // remove all non ascii characters
        $pattern = '/[^a-zA-Z0-9_-]/i';
        $string = strtolower(preg_replace($pattern, '', $string));

        return preg_replace('/-+/', '-', $string);
    }
}
