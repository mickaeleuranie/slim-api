<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

/**
 * Display documentation
 */
// $app->get('/documentation/{token:[0-9a-zA-Z]+}', \api\controllers\DocumentationController::class . ':view');
// $app->get('/documentation/{token:[0-9a-zA-Z]+}/', \api\controllers\DocumentationController::class . ':view');
// $app->get('/documentation/{token:[0-9a-zA-Z]+}/{name:[a-zA-Z]+}', \api\controllers\DocumentationController::class . ':view');
// $app->get('/documentation/{token:[0-9a-zA-Z]+}/{name:[a-zA-Z]+}/', \api\controllers\DocumentationController::class . ':view');

/**
 * Display documentation index
 */
$app->get('/documentation/', \api\controllers\DocumentationController::class . ':index');
$app->get('/documentation', \api\controllers\DocumentationController::class . ':index');
