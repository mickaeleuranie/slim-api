<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

require __DIR__ . '/../handlers/ApiErrorHandler.php';
require __DIR__ . '/../handlers/NotFoundHandler.php';

$container = $app->getContainer();

$container['errorHandler'] = function ($c) {
    return new api\handlers\ApiErrorHandler($c['logger']);
};

$container["phpErrorHandler"] = function ($container) {
    return new api\handlers\PhpErrorHandler();
};

$container['notFoundHandler'] = function ($c) {
    return new api\handlers\NotFoundHandler($c['logger']);
};
