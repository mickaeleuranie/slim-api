<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

$container = $app->getContainer();

// $container["JwtAuthentication"] = function ($container) {
//     return new \Tuupola\Middleware\JwtAuthentication([
//         "path" => "/",
//         "ignore" => ["/token", "/info"],
//         "secret" => getenv("JWT_SECRET"),
//         "logger" => $container["logger"],
//         "attribute" => false,
//         "relaxed" => ["192.168.50.52", "127.0.0.1", "localhost"],
//         "error" => function ($request, $response, $arguments) {
//             return new \Response\UnauthorizedResponse($arguments["message"], 401);
//         },
//         "before" => function ($request, $response, $arguments) use ($container) {
//             $container["token"]->hydrate($arguments["decoded"]);
//         }
//     ]);
// };

$container["Cors"] = function ($container) {
    return new \Tuupola\Middleware\Cors([
        "logger" => $container["logger"],
        "origin" => ["*"],
        "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
        "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
        "headers.expose" => ["Authorization", "Etag"],
        "credentials" => true,
        "cache" => 60,
        "error" => function ($request, $response, $arguments) {
            return new \Response\UnauthorizedResponse($arguments["message"], 401);
        }
    ]);
};

$container["Negotiation"] = function ($container) {
    return new \Gofabian\Negotiation\NegotiationMiddleware([
        "accept" => ["application/json"]
    ]);
};

// $app->add("JwtAuthentication");
$app->add("Cors");
$app->add("Negotiation");

// security check
$app->add(new \api\middlewares\SecurityMiddleware($app->getContainer()));

// availability check
$app->add(new \api\middlewares\AvailabilityMiddleware($app->getContainer()));

// get user IP
$checkProxyHeaders = true; // Note: Never trust the IP address for security processes!
$trustedProxies = ['10.0.0.1', '10.0.0.2']; // Note: Never trust the IP address for security processes!
$app->add(new RKA\Middleware\IpAddress($checkProxyHeaders, $trustedProxies));

$container["cache"] = function ($container) {
    return new \Micheh\Cache\CacheUtil;
};
