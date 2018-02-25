<?php

/**
 * This file is part of the Slim API package
 * Api is a helper class serving common framework functionalities.
 *
 * It extends from [[\Api\BaseApi]] which provides the actual implementation.
 * By writing your own Api class, you can customize some functionalities of [[\api\BaseApi]].
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api;

use api\models\User;
use api\exceptions\NotFoundException;
use api\filters\OAuth2AccessFilter;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Api extends BaseApi
{
    /**
     * Multiple check before executing action
     *  - security check
     *  - maintenance mode
     *  - API version
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return bool
     */
    public static function check(Request $request, Response $response, $container): bool
    {
        // Security check
        // ==============

        // get called action
        $callParams = explode('/', ltrim($request->getUri()->getPath(), '/'));

        // if no params in url, go to default route
        if ('/' === $request->getUri()->getPath() || empty($callParams) || empty($callParams[0]) || empty($callParams[1])) {
            return false;
        }

        if (empty($callParams[1])) {
            return false;
        }

        $version = !empty($callParams[0]) ? $callParams[0] : null;
        $controller = !empty($callParams[1]) ? $callParams[1] : null;
        $action = !empty($callParams[2]) ? $callParams[2] : null;

        if (empty($version)) {
            throw new NotFoundException(Api::t('error.not_found'));
        }

        // handle specific case: documentation
        // in this case, we first check if controller exists
        // then we do normal checks
        if ($version === 'documentation') {
            $controller = $version;

            // check that documentation controller exists
            $className = '\\api\\controllers\\' . ucfirst($controller) . 'Controller';
            if (!class_exists($className)) {
                throw new NotFoundException(Api::t('error.not_found'));
            }

            return true;
        }

        if (empty($controller)) {
            throw new NotFoundException(Api::t('error.not_found'));
        }

        // common case, check controller and action
        // check that controller exists
        $className = '\\api\\' . $version . '\\controllers\\' . ucfirst($controller) . 'Controller';
        if (!class_exists($className)) {
            // try to find class without version number (default API)
            $className = '\\api\\controllers\\' . ucfirst($controller) . 'Controller';
            if (!class_exists($className)) {
                throw new NotFoundException(Api::t('error.not_found'));
            }
        }
        $class = new $className($container);

        // check that action exists
        if (empty($action) || !method_exists($className, $action)) {
            throw new NotFoundException(Api::t('error.not_found'));
        }

        // API key and token are given if two different ways, according to API version
        $requestHeadersPrefix = str_replace('-', '_', getenv('API_REQUEST_HEADERS_PREFIX'));
        switch ($version) {
            case 'v1':
                $apiKey = $request->getQueryParam('apikey');
                $accessToken  = $request->getQueryParam('access_token');

                // handle new API key and token set in V1
                $headers = $request->getHeaders();
                if (empty($apiKey) && !empty($headers['HTTP_' . $requestHeadersPrefix . '_KEY']) && !empty($headers['HTTP_' . $requestHeadersPrefix . '_KEY'][0])) {
                    $apiKey = $headers['HTTP_' . $requestHeadersPrefix . '_KEY'][0];
                }

                if (empty($accessToken) && !empty($headers['HTTP_' . $requestHeadersPrefix . '_TOKEN']) && !empty($headers['HTTP_' . $requestHeadersPrefix . '_TOKEN'][0])) {
                    $accessToken = $headers['HTTP_' . $requestHeadersPrefix . '_TOKEN'][0];
                }
                break;

            default:
                $apiKey = $request->getHeader('HTTP_' . $requestHeadersPrefix . '_KEY');
                $accessToken  = $request->getHeader('HTTP_' . $requestHeadersPrefix . '_TOKEN');

                if (!empty($apiKey[0])) {
                    $apiKey = $apiKey[0];
                }

                if (!empty($accessToken[0])) {
                    $accessToken  = $accessToken[0];
                }
        }

        self::setApiKey($apiKey);
        self::setApiToken($accessToken);

        $oAuth2AccessFilter = new OAuth2AccessFilter;
        $oAuth2AccessFilter->beforeActionCheck(
            $request,
            $container->settings['authorizedKeys'],
            $class->accessRules(),
            $action,
            $apiKey,
            $accessToken
        );

        return true;
    }

    /**
     * Check maintenance and version
     * @todo Use database value instead of CONSTANT to be able to change those values from administration
     * - maintenance
     * - version number
     * @param array $list Default to ['maintenance']
     * @return array
     */
    public static function checkAvailability(array $list = ['maintenance', 'version']): array
    {
        // Maintenance check
        // =================

        $data = [];

        if (in_array('maintenance', $list)) {
            // handle maintenance mode for each application
            if (!empty(static::$request->getQueryParam('apikey'))) {
                $apiKey = static::$request->getQueryParam('apikey');
                $maintenanceMode = false;

                switch (Api::$container->settings['authorizedKeys'][$apiKey]) {
                    // add each custom case

                    default:
                        // nothing to do here
                }
            }

            // handle global maintenance mode
            if ('true' === getenv('MAINTENANCE_MODE')) {
                $data['maintenance'] = true;
            }
        }

        // Version check
        // =============

        if (in_array('version', $list)
            && !empty(static::$request->getQueryParam('apikey'))
            && !empty(static::$request->getQueryParam('v'))
        ) {
            // handle maintenance mode for each application
            $apiKey = static::$request->getQueryParam('apikey');
            $version = static::$request->getQueryParam('v');

            switch (Api::$container->settings['authorizedKeys'][$apiKey]) {
                // add each custom case

                default:
                    // nothing to do here
            }
        }

        return $data;
    }
}
