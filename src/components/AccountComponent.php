<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\components;

use api\Api;
use api\models\User;
use api\models\EmailUpdate;
use api\exceptions\BadRequestException;
use api\exceptions\UnauthorizedException;

class AccountComponent
{

    /**
     * Remove old email key by user id
     * @param integer $userId User id
     * @return boolean
     */
    public static function removeOldEmailKeyByUser($userId)
    {
        return EmailUpdate::where(['user_id' => $userId])->delete();
    }

    /**
     * First step before updating email : generating email reset link
     * @throws \api\exceptions\ApiException
     * @throws \api\exceptions\BadRequestException
     * @param \api\models\User $user
     * @param $email
     * @param $password
     * @return boolean
     */
    public static function generateEmailResetLink(User $user, $email, $password)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestException(Api::t('error.account.invalid_email'));
        }

        // test if email is already user's email
        if ($user->getEmail() === $email) {
            throw new BadRequestException(Api::t('error.account.same_email'));
        }

        // test if email already exists
        // handle socially logged user
        // from social provider, field is social_email instead of email
        $field = 'email';
        if ($user->social_provider) {
            $field = 'social_email';
        }
        $userDuplicate = User::where([$field => $email])
            ->where('id', '<>', $user->id)
            ->first();

        // @TODO prevent registered email search
        if (!empty($userDuplicate)) {
            throw new BadRequestException(Api::t('error.account.taken_email'));
        }

        // check password
        // @TODO prevent password attack
        if (!$user->validatePassword($password)) {
            throw new BadRequestException(Api::t('error.account.wrong_password'));
        }

        // remove old security key
        self::removeOldEmailKeyByUser($user->id);

        // generate security key
        $key = substr(md5($user->activkey . microtime()), 0, 30);
        $emailUpdate          = new EmailUpdate;
        $emailUpdate->user_id = $user->id;
        $emailUpdate->email   = $email;
        $emailUpdate->key     = $key;

        if (true !== $emailUpdate->save()) {
            Api::$dblogger->log(Api::t('error.account.creating_security_key_details', ['%message%' => $emailUpdate->errors(true)]), 'error');
            throw new ApiException(Api::t('error.account.creating_security_key'));
        }

        $url = getenv('API_PROTOCOL') . '://' . getenv('API_DOMAIN_NAME') . '/emailupdate/k/' . $key;

        $subject = Api::t('mail.title.email_reset');
        $message = Api::t('mail.content.email_reset', ['%url%' => $url]);

        EmailComponent::send($user->getEmail(), $subject, $message);

        return true;
    }

    /**
     * Load all filters in a single array
     * @return array
     */
    public static function loadFilters()
    {
        $filters = [];

        return $filters;
    }

    /**
     * Update user's password
     * @param string $oldPassword
     * @param string $newPassword
     * @return boolean
     */
    public static function updatePassword($oldPassword, $newPassword)
    {
    }

    /**
     * Edit user's profile
     * @throws \yii\web\HttpException
     * @throws \yii\web\BadRequestException
     * @throws \yii\web\UnauthorizedHttpException
     * @param \api\common\model\User $user
     * @param array $data List of user data to change
     * @return api\models\User
     */
    public static function edit(User $user, array $data = [])
    {
        if (empty($data)) {
            return $user;
        }

        // handle username
        $saveUser = false;
        $updatedUsername = false;
        if (isset($data['username'])) {
            if (empty($data['username'])) {
                throw new BadRequestException(Api::t('error.account.empty_username'));
            }

            // check that username doesn't alredy exists
            $sameUser = \api\common\models\User::where(['username' => $data['username']])
                ->where('id', '<>', $user->id)
                ->first();

            if (!empty($sameUser)) {
                throw new UnauthorizedException(Api::t('error.account.taken_username'));
            }

            if ($data['username'] !== $user->username) {
                $user->username = $data['username'];

                $saveUser = true;
                $updatedUsername = true;
            }
        }

        // handle email
        if (isset($data['email'])) {
            if (empty($data['email'])) {
                throw new BadRequestException(Api::t('error.acount.empty_email'));
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new BadRequestException(Api::t('error.account.invalid_email'));
            }

            // password is needed to change email
            if (empty($data['password'])) {
                throw new BadRequestException(Api::t('error.missing_parameter'));
            }
            $data['email'] = $data['email'];
            self::generateEmailResetLink(
                $user,
                $data['email'],
                $data['password']
            );
        }

        // handle password
        $updatedPassword = false;
        if (isset($data['password_new'])) {
            if (empty($data['password_new'])) {
                throw new BadRequestException(Apii::t('error.account.empty_new_password'));
            }

            // password is needed to change email
            if (empty($data['password'])) {
                throw new BadRequestException(Api::t('error.missing_parameter'));
            }

            // check password validity
            if (!$user->validatePasswordFormat($data['password_new'])) {
                throw new BadRequestException(Api::t('error.account.password_check'));
            }

            // check password
            // if ($user->password !== md5($data['password'])) {
            if (!$user->validatePassword($data['password'])) {
                throw new BadRequestException(Api::t('error.account.wrong_password'));
            }

            // @TODO prevent setting same password as the old one

            $user->password_hash = Api::$security->generatePasswordHash($data['password_new']);
            $user->password_reset_token = Api::$security->generateRandomString() . '_' . time();

            $saveUser = true;
            $updatedPassword = true;
        }

        // handle locale
        $updatedLocale = false;
        if (isset($data['locale'])) {
            if (empty($data['locale'])) {
                throw new BadRequestException(Api::t('error.account.empty_locale'));
            }

            $user->locale = Api::formatLocale($data['locale']);
            $saveUser = true;
        }

        if ($saveUser && true !== $user->save()) {
            Api::$dblogger->log(Api::t('error.account.editing_details', ['%message%' => $user->errors(true)]), 'error');
            throw new ApiException(Api::t('error.account.editing'));

            // send email to user to tell him that username has been changed
            if ($updatedUsername) {
                $subject = Api::t('email.title.updated_username');
                $message = Api::t(
                    'email.content.updated_username',
                    [
                        '%username%' => $data['username'],
                        '%url%'      => getenv('API_PROTOCOL') . '://' . getenv('API_DOMAIN_NAME') . '/contact',
                    ]
                );

                EmailComponent::send($user->getEmail(), $subject, $message);
            }

            // send email to user to tell him that password has been changed
            if ($updatedPassword) {
                $subject = \Yii::t('email.title.updated_password');
                $message = \Yii::t(
                    'email.content.updated_password',
                    [
                        '%url%' => getenv('API_PROTOCOL') . '://' . getenv('API_DOMAIN_NAME') . '/contact',
                    ]
                );

                EmailComponent::send($user->getEmail(), $subject, $message);
            }
        }

        return $user;
    }
}
