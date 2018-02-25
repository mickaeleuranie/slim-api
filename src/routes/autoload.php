<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

$list = scandir(__DIR__);
foreach ($list as $file) {
    if (!in_array($file, ['.', '..', '.gitignore', 'autoload.php'])) {
        require __DIR__ . '/' . $file;
    }
}
