<?php

/**
 * Use this file to define SWAGGER needed data
 *
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\controllers;

/**
 * @SWG\Swagger(
 *     basePath="/api",
 *     host="your-domain.com",
 *     schemes={"https"},
 *     produces={"application/json"},
 *     consumes={"application/json"},
 *     @SWG\Info(
 *         version="1.0",
 *         title="Slim API",
 *         description="Slim API",
 *         termsOfService="https://your-domain.com/cgu/",
 *         @SWG\Contact(name="Slim API Team", url="https://your-domain.com"),
 *     )
 * ),
 *
 * @SWG\Parameter(
 *     parameter="apikey",
 *     description="API key",
 *     in="query",
 *     name="apikey",
 *     type="string",
 *     required=true,
 * ),
 *
 * @SWG\Parameter(
 *     parameter="access_token",
 *     description="Access token",
 *     in="query",
 *     name="access_token",
 *     type="string",
 *     required=true,
 * ),
 *
 * @SWG\Parameter(
 *     parameter="id",
 *     description="ID",
 *     in="query",
 *     name="id",
 *     type="integer"
 * ),
 *
 * @SWG\Response(
 *     response="signup",
 *     description="User added",
 *     @SWG\Schema(
 *         type="object",
 *         @SWG\Property(
 *             property="access_token",
 *             description="Generated token for user. This token has illimited lifetime",
 *             type="string",
 *         ),
 *         @SWG\Property(
 *             property="username",
 *             description="Username",
 *             type="string",
 *         ),
 *         @SWG\Property(
 *             property="autologin_token",
 *             description="User's autologin token",
 *             type="string",
 *         ),
 *         @SWG\Property(
 *             property="locale",
 *             description="User's locale",
 *             type="string",
 *         ),
 *     )
 * ),
 *
 */
class ApiController extends Controller
{
}
