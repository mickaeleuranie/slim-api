<?php

namespace api\components;

use api\Api;
use api\exceptions\ApiException;
use api\exceptions\BadRequestException;
use api\exceptions\ForbiddenException;
use api\exceptions\NotFoundException;
use api\exceptions\UnauthorizedException;
use api\models\AuthAssignment;
use api\models\OAuthAccessToken;
use api\models\OauthClient;
use api\models\PasswordReset;
use api\models\SignupSocialForm;
use api\models\User;
use api\components\EmailComponent;

class UserComponent
{
    /**
     * Create new user, with API access
     * @throws \api\exceptions\ApiException
     * @throws \api\exceptions\UnauthorizedException
     * @param array $data
     * @param boolean $sendMail
     * @return \api\models\User
     */
    public static function create($data, $sendMail = false): User
    {
        $user = User::where(['email' => $data['email']])->first();
        if (!empty($user)) {
            throw new UnauthorizedException(Api::t('error.user_already_exists'));
        }

        $date = new \DateTime();

        // create user
        $user = new User;

        // check password validity
        if (!$user->validatePasswordFormat($data['password'])) {
            throw new BadRequestException(Api::t('error.account.password_check'));
        }

        $user->username             = empty($data['username']) ? substr(str_replace('@', '_', $data['email']), 0, 45) : $data['username'];
        $user->email                = $data['email'];
        $user->confirmation         = false;
        $user->activkey             = md5(microtime() . $data['password']);
        $user->auth_key             = Api::$security->generateRandomString();
        $user->password_hash        = Api::$security->generatePasswordHash($data['password']);
        $user->password_reset_token = Api::$security->generateRandomString() . '_' . time();
        $user->generateAutologinToken();
        $user->created_at           = $date->format('Y-m-d H:i:s');
        $user->updated_at           = $date->format('Y-m-d H:i:s');
        $user->status               = 1;
        $user->locale               = 'fr';

        // handle social provider data
        if (!empty($data['social_provider'])) {
            $user->social_provider      = $data['social_provider'];
        }
        if (!empty($data['social_identifier'])) {
            $user->social_identifier    = $data['social_identifier'];
        }
        if (!empty($data['social_email'])) {
            $user->social_email         = $data['social_email'];
        }

        // handle users created from CLI commands
        if (method_exists(Api::$request, 'getParams') && !empty(Api::$request->getParams()[0]) && in_array(Api::$request->getParams()[0], ['migrate'])) {
            $user->ip = '0.0.0.0';
        } else {
            $user->ip               = (!empty(Api::$request->getAttribute('ip_address')))
                ? Api::$request->getAttribute('ip_address')
                : '0.0.0.0'
            ;
        }
        if (true !== $user->save()) {
            $message = ErrorComponent::getModelErrorMessage($user);
            throw new ApiException(Api::t('error.creating_user', ['%error%' => $message]));
        }

        // create API access
        $client = new OauthClient;
        $client->client_id     = $data['email'];
        $client->client_secret = Api::$container->settings['api']['client_secret'];
        $client->redirect_uri  = 'https://' . getenv('API_DOMAIN_NAME');
        $client->grant_types   = 'client_credentials authorization_code password implicit';
        $client->scope         = 'api';
        $client->user_id       = $user->id;
        if (true !== $client->save()) {
            Api::$dblogger->log(Api::t('error.adding_user_api_access_details', ['%error%' => $client->errors(true)]), 'error');
            throw new ApiException(Api::t('error.adding_user_api_access'));
        }

        // generate access token
        $user->generateAccessToken();

        // add user user's role
        $auth = new AuthAssignment;
        $auth->item_name = 'user';
        $auth->user_id = $user->id;
        $auth->save();

        if (true !== $user->save()) {
            $message = ErrorComponent::getModelErrorMessage($user);
            throw new ApiException(Api::t('error.updating_user_family', ['%error%' => $message]));
        }

        if (true === $sendMail) {
            try {
                // send activation mail

                $confirmationLink = 'https://' . getenv('API_DOMAIN_NAME') . '/account/confirm/k/' . $user->activkey . '/m/' . urlencode($user->email);
                $subject = Api::t('mail.title.acount_confirmation');
                $message = Api::t(
                    'mail.content.signin_confirmation',
                    [
                        '%confirmationLink%'               => '<a href="' . $confirmationLink . '">' . $confirmationLink . '</a>',
                        '%accountConfirmValidityDuration%' => Api::$container->settings['account']['accountConfirmValidityDuration'],
                    ]
                );
                EmailComponent::send($user->getEmail(), $subject, $message);
            } catch (Exception $e) {
                throw new ApiException(Api::t('error.email.signing_confirmation'));
            }
        }

        return $user;
    }

    /**
     * Send lost password code to given email address
     * @param string $email
     * @return boolean Always true to prevent emails search
     */
    public static function sendLostPasswordCode($email)
    {
        $user = User::where(['email' => $email])->first();
        if (empty($user)) {
            return true;
        }

        // delete old existing code
        PasswordReset::where(['email' => $email])->delete();

        // generate code
        $activationKey = substr(md5($user->activkey . microtime()), 0, 30);
        $passwordReset                 = new PasswordReset;
        $passwordReset->user_id        = $user->id;
        $passwordReset->email          = $email;
        $passwordReset->activation_key = $activationKey;

        if (true !== $passwordReset->save()) {
            Api::$dblogger->log(Api::t('error.password_code.creating_details', ['%message%' => $passwordReset->errors(true)]), 'error');
            throw new ApiException(Api::t('error.password_code.creating'));
        }

        $subject = Api::t('mail.title.password_code');
        $message = Api::t('mail.content.password_code', ['%code%' => $passwordReset->activation_key]);

        EmailComponent::send($user->email, $subject, $message);
    }

    /**
     * Update lost password
     * @param string $code
     * @param string $password
     * @return boolean
     */
    public static function updateLostPassword($code, $password)
    {
        // check password
        User::validatePasswordFormat($password);

        // check code
        $passwordReset = PasswordReset::where(['activation_key' => $code])->first();

        if (empty($passwordReset)) {
            Api::$dblogger->log(Api::t('error.password_code.not_found_details', ['%code%' => $code]), 'error');
            throw new ApiException(Api::t('error.password_code.not_found'));
        }

        // check validity
        $now = new \DateTime;
        $expires = new \DateTime($passwordReset->time);
        $expires->add(new \DateInterval("PT24H"));

        if ($now->format('Y-m-d H:i:s') > $expires->format('Y-m-d H:i:s')) {
            // remove lost password code because it's expired
            $passwordReset->delete();
            Api::$dblogger->log(Api::t('error.password_code.expired_details', ['%code%' => $code]), 'error');
            throw new ApiException(Api::t('error.password_code.expired'));
        }

        $passwordReset->user->password_hash = Api::$security->generatePasswordHash($password);
        $passwordReset->user->password_reset_token = Api::$security->generateRandomString() . '_' . time();

        if (!$passwordReset->user->validate() || !$passwordReset->user->save()) {
            $message = ErrorComponent::getModelErrorMessage($passwordReset->user);
            Api::$dblogger->log(Api::t('error.password_code.updating_details', ['%message%' => $passwordReset->user->errors(true)]), 'error');
            throw new ApiException(Api::t('error.password_code.updating'));
        }

        // delete old existing code
        PasswordReset::where(['email' => $passwordReset->user->email])->delete();

        $subject = Api::t('email.title.password_updated');
        $message = Api::t('email.content.password_updated');

        EmailComponent::send($passwordReset->user->email, $subject, $message);

        return true;
    }

    /**
     * Edit user's profile
     * @throws ApiException
     * @throws BadRequestException
     * @throws UnauthorizedException
     * @param User $user
     * @param array $data List of user data to change
     * @return User
     */
    public static function edit(User $user, array $data = [])
    {
        if (empty($data)) {
            return true;
        }

        // handle username
        if (isset($data['username'])) {
            if (empty($data['username'])) {
                throw new BadRequestException(Api::t('error.profile.empty_username'));
            }
            // do nothing if username is the same
            if ($user->username !== $data['username']) {
                // check that username isn't already taken
                $userCheck = User::where(['username' => $data['username']])->first();
                if (!empty($userCheck)) {
                    throw new BadRequestException(Api::t('error.account.taken_username'));
                }
                $user->username = $data['username'];
            }
        }

        if (true !== $user->save()) {
            echo $user->errors(true);
            Api::$dblogger->log(Api::t('error.profile.editing_details', ['%message%' => $user->errors(true)]), 'error');
            throw new ApiException(Api::t('error.profile.editing'));
        }

        return $user;
    }

    /**
     * Delete user and his API access
     * @param integer $id
     * @throws \api\exceptions\NotFoundException
     */
    public static function delete($id)
    {
        $user = User::find($id);

        if (empty($user)) {
            throw new NotFoundException(\Yii::t('api/error', 'deleteUser :: User not found'));
        }

        return $user->delete();
    }

    /**
     * Log user in
     * @param string $email
     * @param string $password
     * @return array
     */
    public static function login($email, $password): array
    {
        //check user
        $user = User::where(['email' => $email])->first();

        if (empty($user)) {
            throw new UnauthorizedException(Api::t('error.login'));
        }

        // check password
        if (!$user->validatePassword($password)) {
            throw new UnauthorizedException(Api::t('error.login'));
        }

        // generate access token
        $user->generateAccessToken();

        // update last visit date
        $user->updateLastVist();

        // remove all authorisation code and password reset code existing for this user
        // when successfull login
        PasswordReset::where(['email' => $user->email])->delete();

        return [
            'access_token' => $user->token->access_token,
            'user'         => $user
        ];
    }

    /**
     * Log user in from autologin token
     * @param string $email
     * @param string $password
     * @return array
     */
    public static function autologin($token): array
    {
        // get user id from autologin token
        $user = User::getByAutoLoginAccessToken($token);

        // if user not found, error
        if (empty($user)) {
            throw new ForbiddenException(Api::t('error.not_allowed'));
        }

        // generate new autologin_token
        // uncomment to activate this function
        // with this activated, autologin whon't work
        // if user uses more than one device,
        // as this autologin token will work only once
        // $user->generateAutologinToken();
        // $user->save();

        // generate access token
        $user->generateAccessToken();

        // update last visit date
        $user->updateLastVist();

        // remove all authorisation code and password reset code existing for this user
        // when successfull login
        // uncomment to activate this function
        // PasswordReset::where(['email' => $user->email])->delete();

        return [
            'access_token' => $user->token->access_token,
            'user'         => $user
        ];
    }

    /**
     * Load user data for mobile application
     * @param \api\models\User $user
     * @param array $filters Additional fields we want to load
     * @return array
     */
    public static function load($user, array $filters = [])
    {

        $data = [];

        // locale
        $data['locale'] = $user->locale;

        // last update date
        $data['updated_at'] = $user->updated_at;

        // username
        if (in_array('username', $filters)) {
            $data['username'] = $user->autologin_token;
        }

        // autologin
        if (in_array('autologin', $filters)) {
            $data['autologin_token'] = $user->autologin_token;
        }

        return $data;
    }

    /**
     * Get user from provider id
     * @param string $id
     * @return \api\common\models\User
     */
    public static function getUserFromProviderId($id)
    {
        return User::where(['social_identifier' => $id])->first();
    }

    /**
     * Log user from provider id
     * @param \api\common\models\User
     * @param integer $providerId
     * @return array
     */
    public static function socialLogin(User $user, $providerId)
    {
        // generate access token
        $user->generateAccessToken();

        // update last visit date
        $user->updateLastVist();

        // remove all authorisation code and password reset code existing for this user
        // when successfull login
        PasswordReset::where(['email' => $user->email])->delete();

        return [
            'access_token' => $user->token->access_token,
            'user'         => $user
        ];
    }

    /**
     * Signup user from social network
     * @param string $provider
     * @param array $data
     * @throws BadRequestHttpException
     * @throws HttpException
     */
    public static function socialSignup($provider, array $data)
    {
        $username = Api::$container->settings['signup']['hybridauthUsernamePrefix'] . strtolower($provider) . '_' . $data['social_identifier'];
        $userModel = new SignupSocialForm;

        $userModel->username = $username;
        $userModel->social_provider   = $provider;
        $userModel->social_identifier = $data['social_identifier'];
        $userModel->social_email      = $data['social_email'];

        // check data validity
        if (true !== $userModel->validate()) {
            $message = ErrorComponent::getModelErrorMessage($userModel);
            throw new UnauthorizedException(Api::t('error.user_already_exists'));
        }

        // create user
        $data['email']           = $username . Api::$container->settings['signup']['hybridauthEmailPostfix'];
        $data['social_provider'] = $provider;
        $data['username']        = $username;
        $data['password']        = Api::$container->settings['signup']['hybridauthPasswordPrefix'] . $username;

        return self::create($data, true);
    }
}
