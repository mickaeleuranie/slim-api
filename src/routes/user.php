<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

/**
 * Get user status
 */
$app->get('/v1/user/status', \api\controllers\UserController::class . ':status');

/**
 * Get user
 * @param integer $id
 */
$app->get('/v1/user/get/{id:[0-9]+}', \api\controllers\UserController::class . ':get');

/**
 * Signup user
 */
$app->post('/v1/user/signup', \api\controllers\UserController::class . ':signup');

/**
 * Log user in
 */
$app->post('/v1/user/login', \api\controllers\UserController::class . ':login');

/**
 * Social log in
 */
$app->post('/v1/user/sociallogin', \api\controllers\UserController::class . ':sociallogin');

/**
 * Log user in from autologin token
 */
$app->post('/v1/user/autologin', \api\controllers\UserController::class . ':autologin');

/**
 * Update user profile
 */
$app->post('/v1/user/edit', \api\controllers\UserController::class . ':edit');
$app->post('/v1/user/profile', \api\controllers\UserController::class . ':profile');

/**
 * Update user account
 */
$app->post('/v1/user/account', \api\controllers\UserController::class . ':account');

/**
 * Contact
 */
$app->post('/v1/user/contact', \api\controllers\UserController::class . ':contact');

/**
 * Send lost password code
 */
$app->post('/v1/user/sendlostpasswordcode', \api\controllers\UserController::class . ':sendlostpasswordcode');

/**
 * Update lost password
 */
$app->post('/v1/user/updatelostpassword', \api\controllers\UserController::class . ':updatelostpassword');
