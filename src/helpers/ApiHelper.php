<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\helpers;

class ApiHelper
{

    /**
     * Include files from given folder
     * @param string $folder Folder name
     * @param string $first First file to require
     * @param array $ignore File/folder names to ignore
     * @param boolean $recursive Default to false
     * @todo Recursive mode
     * @return void
     */
    public function includeFilesFrom($folder, $first = null, array $ignore = [], $recursive = false)
    {
        $list = scandir($folder);
        $ignore = array_merge(['.', '..', '.gitignore'], $ignore);

        // require first file first
        if ($first) {
            require_once $folder . $first;
            $ignore[] = $first;
        }

        foreach ($list as $file) {
            if (!in_array($file, $ignore)) {
                require_once $folder . $file;
            }
        }
        return;
    }

    /**
     * Get browser language
     * @see http://stackoverflow.com/questions/3770513/detect-browser-language-in-php#3770616
     * @see https://gist.github.com/humantorch/d255e39a8ab4ea2e7005
     */
    public function getSupportedLanguage($httpAcceptLanguage)
    {
        $availableLanguages = \Yii::$app->params['availableLanguages'];
        $availableLanguages = array_flip($availableLanguages);
        $langs = array();
        preg_match_all('~([\w-]+)(?:[^,\d]+([\d.]+))?~', strtolower($httpAcceptLanguage), $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            list($a, $b) = explode('-', $match[1]) + array('', '');
            $value = isset($match[2]) ? (float) $match[2] : 1.0;
            if (isset($availableLanguages[$match[1]])) {
                $langs[$match[1]] = $value;
                continue;
            }
            if (isset($availableLanguages[$a])) {
                $langs[$a] = $value - 0.1;
            }
        }
        if ($langs) {
            arsort($langs);
            return key($langs); // We don't need the whole array of choices since we have a match
        } else {
            return \Yii::$app->params['defaultLanguage'];
        }
    }
}
