<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\filters;

use api\models\User;
use api\Api;
use api\components\ErrorComponent;
use api\exceptions\ApiException;
use api\exceptions\ForbiddenException;
use Psr\Http\Message\ServerRequestInterface as Request;

class OAuth2AccessFilter
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param array  $authorizedKeys
     * @param array  $accessRules
     * @param string $action
     * @param string $apiKey
     * @param string $accessToken
     * @return bool
     * @throws ForbiddenException
     * @throws \yii\base\InvalidConfigException
     */
    public function beforeActionCheck(Request $request, array $authorizedKeys, array $accessRules, $action, $apiKey, $accessToken = null): bool
    {
        // check environment
        if (!in_array(getenv('ENV'), ['development', 'test', 'preproduction', 'production'])) {
            throw new ApiException(Api::t('error.unknown_environment'));
        }

        // check if given api key is authorized
        if (empty($apiKey) || empty($authorizedKeys[$apiKey])) {
            throw new ForbiddenException();
        }

        list($publicActions, $actionsScopes) = $this->analyzeAccessRules($accessRules, $action);

        // if action is public, don't check action accessibility
        if (in_array($action, $publicActions)) {
            // set user if token is given application wide
            if (!empty($accessToken)) {
                // get user from accessToken
                $user = User::getByAccessToken($accessToken);

                if (empty($user)) {
                    throw new ForbiddenException();
                }

                Api::setUser($user);

                // set localisation
                $this->setLocale($user, $apiKey);
            }

            // log call
            $this->logCall($request);

            return true;
        }

        // return true; // @TODO uncomment when working on token access

        if (empty($accessToken)) {
            throw new ForbiddenException();
        }

        // else, if not public, check user access

        $scopes = isset($actionsScopes[$action]) ? $actionsScopes[$action] : '';

        // get user from accessToken
        $user = User::getByAccessToken($accessToken);

        if (empty($user)) {
            throw new ForbiddenException();
        }

        if (!static::checkAccess($user, $accessToken, $scopes)) {
            throw new ForbiddenException();
        }

        // save user application wide
        Api::setUser($user);

        // update token availability (+ 24h)
        $user->generateAccessToken();

        // set localisation
        $this->setLocale($user, $apiKey);

        // log call
        $this->logCall($request);

        return true;
    }

    /**
     * Handle authentication
     * @param User $user
     * @param string $accessToken
     * @param array $scopes
     * @return boolean
     */
    private static function checkAccess(User $user, $accessToken, $scopes)
    {

        if (empty($user)) {
            return false;
        }

        // check if token has expired
        $now = new \DateTime;
        $expire = date_create($user->expires);
        $diff = $now->diff($expire);

        if (0 < $diff->invert) {
            return false;
        }

        // check if user as access (from scopes)
        if (empty($scopes)) {
            return true;
        }

        return self::compareRolesWithScope($user->roles, $scopes);
    }

    /**
     * Compare role with scope
     * if super_admin and scope is admin, must return true
     * if role not in scopes, return false
     * @param \Illuminate\Database\Eloquent\Collection $roles
     * @param array $scopes
     * @return boolean
     */
    private static function compareRolesWithScope(\Illuminate\Database\Eloquent\Collection $roles, array $scopes)
    {
        if (empty($roles)) {
            return false;
        }

        foreach ($roles as $role) {
            if ('super_admin' === $role->item_name || in_array($role->item_name, $scopes)) {
                return true;
            }
        }

        return false;
    }

    private function analyzeAccessRules(array $accessRules, $currentAction)
    {
        $publicActions = [];
        $actionsScopes = [];
        $isMetCurrAction = false;

        foreach ($accessRules as $rule) {
            if (empty($rule['actions'])) {
                $rule['actions'] = [$currentAction];
            }
            if (!empty($rule['actions'])
                && is_array($rule['actions'])
                && in_array($currentAction, $rule['actions'], true)) {
                $isMetCurrAction = true;
                $actions = $rule['actions'];
                $isPublic = null;
                if (isset($rule['allow'])) {
                    if ($rule['allow'] && (empty($rule['roles']) || in_array('?', $rule['roles']))) {
                        $publicActions = array_merge($publicActions, $rule['actions']);
                        $isPublic = true;
                    } elseif ((!$rule['allow'] && (empty($rule['roles']) || in_array('?', $rule['roles'])))
                        || ($rule['allow'] && !empty($rule['roles']) && in_array('@', $rule['roles']))
                    ) {
                        $publicActions = array_diff($publicActions, $rule['actions']);
                        $isPublic = false;
                    }
                }
                if ($isPublic === false && !empty($rule['scopes'])) {
                    $ruleScopes = $rule['scopes'];
                    $scopes = is_array($ruleScopes) ? $ruleScopes : explode(' ', trim($ruleScopes));
                    foreach ($actions as $a) {
                        if (!isset($actionsScopes[$a])) {
                            $actionsScopes[$a] = $scopes;
                        } else {
                            $actionsScopes[$a] = array_merge($actionsScopes[$a], $scopes);
                        }
                    }
                }
            }
        }

        // action not defined in access rules
        // deny access by default
        if (!$isMetCurrAction) {
            throw new ForbiddenException();
            // $publicActions[] = $currentAction;
        }
        return [$publicActions, $actionsScopes];
    }

    /**
     * Set locale
     *
     * @param User $user
     * @param string $apiKey
     * @return boolean
     */
    private function setLocale(User $user, $apiKey)
    {
        // for bepatient and budgetbox, set locale to fr
        if (!empty($apiKey)) {
            switch ($apiKey) {
                case 'bepatient':
                case 'budgetbox':
                case 'umanlife':
                    Api::$translator->setLocale('fr_FR');
                    return true;
            }
        }

        // set language according to user if access_token is set
        // or if local is added to request
        // get locale
        $locale = null;

        // get from POST
        if (!empty(Api::$request->getParsedBody()['locale'])) {
            $locale = Api::$request->getParsedBody()['locale'];

        // get from GET
        } elseif (!empty(Api::$request->getQueryParam('locale'))) {
            $locale = Api::$request->getQueryParam('locale');
        }

        // if locale is given, set it as default locale
        if (!empty($locale) && in_array($locale, Api::$container['settings']['availableLanguages'])) {
            Api::setLocale($locale);
        } else {
            if (!empty($user)) {
                Api::setLocale($user->locale);

            // try to get it from browser
            } elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $locale = \api\components\ToolComponent::getSupportedLanguage(
                    strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'])
                );
                Api::setLocale($locale);
            }
        }

        return true;
    }

    /**
     * Log calls
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @access private
     * @return void
     */
    private function logCall(Request $request): void
    {
        $call = $request->getUri()->getPath();

        if (true === empty($call)) {
            return;
        }

        if (!preg_match('/^v[0-9]\/(.*)/', $call, $matches) && !empty($matches[1])) {
            return; // shouldn't happend for REST call, but can be true if reading documentation
        }

        // log in database
        // format data
        $now = new \DateTime;
        $callParams = explode('/', ltrim($request->getUri()->getPath(), '/'));

        // API calls
        $date = new \DateTime;
        $data = [
            'level'      => 'info',
            'category'   => 'api',
            'message'    => 'call',
            'referer'    => Api::$request->getUri()->getPath(),
            'controller' => $callParams[1],
            'action'     => $callParams[2],
            'ip'         => $request->getAttribute('ip_address'),
            'date'       => $date->format('Y-m-d H:i:s'),
            'message'    => str_replace('<br>', "\n", ErrorComponent::getRequestDetails(Api::$request)),
        ];

        $data['user_id'] = !empty($user) ? $user->id : null;

        if (true === isset($_SERVER['HTTP_REFERER'])) {
            // prevent "String data, right truncated: 1406 Data too long for column 'referer'"" error
            $data['referer'] = substr($_SERVER['HTTP_REFERER'], 0, 255);
        }

        Api::$dblogger->action($data);
    }
}
