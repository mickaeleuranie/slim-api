<?php
/**
 * Controller for user API
 *
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\controllers;

use api\Api;
use api\components\AccountComponent;
use api\components\ContactComponent;
use api\components\RecipeComponent;
use api\components\ToolComponent;
use api\components\UserComponent;
use api\exceptions\ApiException;
use api\exceptions\NotFoundException;
use api\exceptions\BadRequestException;
use api\exceptions\ForbiddenException;
use api\models\User;
use api\models\FamilyMember;
use Slim\Container as ContainerInterface;

class UserController extends ApiController
{

    public function accessRules()
    {
        return [
            [
                'allow' => true,
                'actions' => [
                    'status',
                    'edit',
                    'profile',
                    'account',
                    'contact',
                ],
                'roles' => ['@'],
            ],
            [
                'allow' => true,
                'actions' => [
                    'get',
                ],
                'roles' => ['@'],
                'scopes' => 'admin',
            ],
            [
                'allow' => true,
                'actions' => [
                    'login',
                    'sociallogin',
                    'signup',
                    'contact',
                    'sendlostpasswordcode',
                    'updatelostpassword',
                ],
                'roles' => ['?'],
            ],
        ];
    }

    /**
     * Check user's status and load his informations
     *
     * @return Json
     */
    public function status($request, $response, $args)
    {
        if (empty(Api::$user)) {
            throw new NotFoundException();
        }

        /**
         * Add specific data according to origin's application
         */
        switch ($this->container->settings['authorizedKeys'][Api::$apiKey]) {
            // set custom load here

            default:
                $data = $this->loadUserData(Api::$user);
                break;
        }

        return $response->withJson($data, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function get($request, $response, $args)
    {
        if (empty($args['id'])) {
            throw new BadRequestException();
        }

        $user = User::find($args['id']);

        if (empty($user)) {
            throw new NotFoundException();
        }

        /**
         * Add specific data according to origin's application
         */
        switch ($this->container->settings['authorizedKeys'][Api::$apiKey]) {
            // set custom get here

            default:
                $data = $this->loadUserData(Api::$user);
        }

        return $response->withJson($data, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Logs user in
     *
     * @return Json
     */
    public function login($request, $response, $args)
    {
        $input = $request->getParsedBody();

        if ((empty($input['login']) && empty($input['email']))
            || (true === empty($input['password']))
        ) {
            throw new BadRequestException(Api::t('error.missing_parameter'));
        }

        $login = (empty($input['login']))
            ? $input['email']
            : $input['login']
        ;

        $loginResult = UserComponent::login($login, $input['password']);
        $data = [
            'access_token' => $loginResult['access_token']
        ];

        /**
         * Add specific data according to origin's application
         */
        switch ($this->container->settings['authorizedKeys'][Api::$apiKey]) {
            // set custom login callback here

            default:
                $userData = $this->loadUserData($loginResult['user']);
                $data = array_merge($data, $userData);
                break;
        }

        return $response->withJson($data, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Logs in a user from social identifier.
     * @todo avoid login call if already have access token
     * @todo don't generate new access token if already have one valid
     *       to prevent all other applications to be disconnected
     * @todo allow login from username
     *
     * @return mixed
     */
    public function sociallogin($request, $response, $args)
    {
        $input = $request->getParsedBody();

        if (true === empty($input['token'])
            || true === empty($input['provider'])
        ) {
            throw new BadRequestException(Api::t('error.missing_parameter'));
        }

        /**
         * Only mobile applications can access this function
         */

        switch ($this->container->settings['authorizedKeys'][Api::$apiKey]) {
            // filter authorized API keys
            // case 'unknownApiKey':
            //     throw new ForbiddenException(Api::t('error.not_allowed'));

            default:
                $firstname = isset($input['name']) ? $input['name'] : null;
                $lastname = null;

                $providerId = null;

                switch ($input['provider']) {
                    case 'facebook':
                        $url = 'https://graph.facebook.com/me?fields=id,email,name,first_name,last_name&access_token=' . $input['token'];
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_URL, $url);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

                        $resultTemp = curl_exec($curl);

                        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                        $result = json_decode($resultTemp);

                        if (empty($result) || empty($result->id)) {
                            throw new BadRequestException(Api::t('error.bad_token'));
                        }

                        $providerId = $result->id;
                        $email      = $result->email;
                        break;

                    default:
                        throw new ForbiddenException(Api::t('error.not_allowed'));
                }

                if (empty($providerId)) {
                    throw new ForbiddenException(Api::t('error.not_allowed'));
                }

                // get user id from provider's id
                $user = UserComponent::getUserFromProviderId($providerId);

                // if user not found, register user
                if (empty($user)) {
                    $user = UserComponent::socialSignup(
                        $input['provider'],
                        [
                            'social_identifier' => $providerId,
                            'social_email'      => $email,
                        ]
                    );

                    $httpCode = 201;
                } else {
                    $httpCode = 200;
                }

                // authenticate user
                $result = UserComponent::socialLogin($user, $providerId);

                // @TODO
                // if (empty($response['access_token'])) {
                //     throw new ForbiddenException(Api::t('error.not_allowed'));
                // }

                $userData = $this->loadUserData($user);

                $data = array_merge($result, $userData);

                return $response->withJson($data, $httpCode, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
    }

    /**
     * Logs in a user from auto login.
     */
    public function autologin($request, $response, $args)
    {
        $input = $request->getParsedBody();

        if (empty($input['token'])) {
            throw new BadRequestException(Api::t('error.missing_parameter'));
        }

        /**
         * Only mobile applications can access this function
         */

        switch (Yii::$app->params['authorizedKeys'][Api::$apiKey]) {
            // filter authorized API keys
            // default:
            //     throw new ForbiddenException(Api::t('error.not_allowed'));
            default:
                // authenticate user
                $loginResult = UserComponent::autologin($input['token']);
                $data = [
                    'access_token' => $loginResult['access_token']
                ];

                $userData = $this->loadUserData($loginResult['user']);
                $data = array_merge($data, $userData);

                return $response->withJson($data, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
    }

    /**
     * Signup
     *
     * @return Json
     *
     * @SWG\Post(
     *     path="/v1/user/signup",
     *     description="Logs user in.",
     *     operationId="api.user.signup",
     *     produces={"application/json"},
     *     tags={"User"},
     *
     *     @SWG\Parameter(
     *         description="Email",
     *         in="body",
     *         name="email",
     *         @SWG\Schema(
     *             type="string",
     *             maxLength=96
     *         )
     *     ),
     *
     *     @SWG\Parameter(
     *         description="Password. Needs to be 8 characters long minimum",
     *         in="body",
     *         name="password",
     *         @SWG\Schema(
     *             type="string",
     *             maxLength=96
     *         )
     *     ),
     *
     *     @SWG\Response(response=201, ref="#/responses/signup"),
     *
     *     @SWG\Response(
     *         response=400,
     *         description="One ore more parameter is missing."
     *     ),
     *
     *     @SWG\Response(
     *         response=500,
     *         description="Error while adding user in database."
     *     )
     * )
     */
    public function signup($request, $response, $args)
    {
        $input = $request->getParsedBody();

        if (empty($input['email'])
            || (empty($input['password']))
        ) {
            throw new BadRequestException(Api::t('error.missing_parameter'));
        }

        $data = [
            'email'     => $input['email'],
            'password'  => $input['password'],
        ];

        $sendMail = true;

        $user = UserComponent::create($data, $sendMail);

        $data = [
            'access_token' => $user->token->access_token,
            'message'      => Api::t('success.signup_confirmation'),
        ];

        $userData = $this->loadUserData($user);
        $data = array_merge($data, $userData);

        return $response->withStatus(201)
                        ->withJson($data);
    }

    /**
     * Load user data after login/socialLogin/status
     * @param \api\models\User
     * @return array
     */
    private function loadUserData(User $user)
    {
        $data = UserComponent::load($user, ['username', 'autologin']);
        // add filters to prevent multiple API calls from application
        $data['filters'] = array_merge(
            AccountComponent::loadFilters($user->locale)
        );

        return $data;
    }

    /**
     * Manage user account
     */
    public function account($request, $response, $args)
    {
        // format data
        $input = $request->getParsedBody();
        $data = [];

        // username
        if (isset($input['username'])) {
            $data['username'] = $input['username'];
        }

        // email
        if (isset($input['email'])) {
            // password is needed to change email
            if (empty($input['password'])) {
                throw new BadRequestException(Api::t('error.missing_parameter'));
            }

            $data['email']    = $input['email'];
            $data['password'] = $input['password'];
        }

        // password
        if (isset($input['password_new'])) {
            // old password is needed to change password
            if (empty($input['password_old'])) {
                throw new BadRequestException(Api::t('error.missing_parameter'));
            }
            $data['password_new'] = $input['password_new'];
            $data['password']     = $input['password_old'];
        }

        // locale
        if (isset($input['locale'])) {
            $data['locale'] = $input['locale'];
        }

        if (empty($data)) {
            throw new BadRequestException(Api::t('error.missing_parameter'));
        }

        AccountComponent::edit(Api::$user, $data);

        $data = [];

        return $response->withJson($data);
    }

    /**
     * Send password reset code
     */
    public function sendlostpasswordcode($request, $response, $args)
    {
        // only authorize some applications
        // switch ($this->container->settings['authorizedKeys'][Api::$apiKey]) {
        //     // filter authorized API keys
        //     // case 'your application':
        //     //     break;
        //     // default:
        //     //     throw new ForbiddenException(Api::t('error.not_allowed'));
        //     case 'your application':
        //         break;

        //     default:
        //         throw new ForbiddenException(Api::t('error.not_allowed'));
        // }

        $input = $request->getParsedBody();

        if (empty($input['email'])) {
            throw new BadRequestException(Api::t('error.missing_parameter'));
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestException(Api::t('error.account.invalid_email'));
        }

        UserComponent::sendLostPasswordCode($input['email']);

        return $response->withStatus(200)->withJson(['success' => true]);
    }

    /**
     * Update lost password
     */
    public function updatelostpassword($request, $response, $args)
    {
        // only authorize some applications
        // switch ($this->container->settings['authorizedKeys'][Api::$apiKey]) {
        //     // filter authorized API keys
        //     // case 'your application':
        //     //     break;
        //     // default:
        //     //     throw new ForbiddenException(Api::t('error.not_allowed'));
        //     case 'your application':
        //         break;

        //     default:
        //         throw new ForbiddenException(Api::t('error.not_allowed'));
        // }

        $input = $request->getParsedBody();
        if (empty($input['code'])
            || empty($input['password'])
            || empty($input['password_confirm'])
        ) {
            throw new BadRequestException(Api::t('error.missing_parameter'));
        }

        if ($input['password'] !== $input['password_confirm']) {
            throw new BadRequestException(Api::t('error.profile.password_and_confirmation'));
        }

        UserComponent::updateLostPassword(
            $input['code'],
            $input['password']
        );

        return $response->withStatus(200)->withJson(['success' => true]);
    }
}
