<?php

/**
 * Model for working with user
 *
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\models;

use api\Api;
use api\exceptions\BadRequestException;
use api\models\FavoriteUserMeal;
use api\models\Newsletter;
use api\models\UserMeal;

class User extends Db
{
    /**
     * @inheritdoc
     */
    protected static $tableName = 'user';

    /**
     * @inheritdoc
     */
    protected static $rules = [
        'email'                 => ['required'],
        'created_at'            => ['required'],
        'superuser'             => ['integer'],
        'status'                => ['integer'],
        'confirmation'          => ['boolean'],
        'extension_installed'   => ['boolean'],
        'got_free_consultation' => ['boolean'],
        'username'              => ['required', 'string', 'max:128'],
        'password_hash'         => ['required', 'string', 'min:8', 'max:255'],
        'autologin_token'       => ['required', 'string', 'min:8', 'max:255'],
        'email'                 => ['email', 'max:128'],
        'activekey'             => ['string', 'max:128'],
        'locale'                => ['string', 'max:10'],
        'ip'                    => ['string', 'max:40'],
    ];

    /**
     * Get roles associated with the user.
     */
    public function roles()
    {
        return $this->hasMany(AuthAssignment::class);
    }

    /**
     * Get access token associated with the user.
     */
    public function token()
    {
        return $this->hasOne(OAuthAccessToken::class);
    }

    /**
     * Get profile
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Get family members
     */
    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class, 'family_id', 'family_id');
    }

    /**
     * Get family profile
     */
    public function familyProfile()
    {
        return $this->hasOne(FamilyMember::class);
    }

    /**
     * Get favorite meals
     */
    public function favoriteMeals()
    {
        return $this->belongsToMany(
            UserMeal::class,
            FavoriteUserMeal::tableName(),
            'user_id',
            'user_meal_id'
        );
    }

    /**
     * Get API keys
     */
    public function apiKey()
    {
        return $this->belongsToMany(
            ApiKey::class,
            UserApiKey::tableName(),
            'user_id',
            'api_key_id'
        );
    }

    /**
     * Get newsletter
     */
    public function newsletter()
    {
        return $this->hasOne(Newsletter::class);
    }

    /**
     * Get user email (social_email if registerd from social network)
     */
    public function getEmail()
    {
        if (!empty($this->social_provider) && !empty($this->social_email)) {
            return $this->social_email;
        }
        return $this->email;
    }

    /**
     * Get ignored items
     */
    public function ignoredItems()
    {
        return $this->belongsToMany(
            Item::class,
            UserIgnoredItem::tableName(),
            'user_id',
            'item_id'
        );
    }

    /**
     * Get liked ingredients
     */
    public function recipeIngredientsLiked()
    {
        return $this->belongsToMany(
            RecipeIngredient::class,
            UserRecipeIngredient::tableName(),
            'user_id',
            'recipe_ingredient_id'
        )->wherePivot('liked', 1);
    }

    /**
     * Get disliked ingredients
     */
    public function recipeIngredientsDisliked()
    {
        return $this->belongsToMany(
            RecipeIngredient::class,
            UserRecipeIngredient::tableName(),
            'user_id',
            'recipe_ingredient_id'
        )->wherePivot('liked', 0);
    }

    /**
     * Check if user has specified role
     * Admin has access to all roles
     * @param string $role
     * @return boolean
     */
    public function checkAccess($role)
    {
        $roles = $this->roles;

        if ($roles->isEmpty()) {
            return false;
        }

        foreach ($roles as $r) {
            if ('admin' === $r->item_name || $role === $r->item_name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user by token
     * @param string $accessToken
     */
    public static function getByAccessToken($accessToken)
    {
        $tokenModel = OAuthAccessToken::where('access_token', $accessToken)->first();

        if (empty($tokenModel)) {
            return null;
        }

        // check expires date if not a permanent user
        if ('permanent' !== $tokenModel->type) {
            $expires = date_create($tokenModel->expires);
            $now = new \DateTime;
            $diff = $expires->diff($now);

            if (! (bool) $diff->invert) {
                return null;
            }
        }

        return $tokenModel->user;
    }

    /**
     * Get user by autologin token
     * @param string $accessToken
     */
    public static function getByAutoLoginAccessToken($autologinToken)
    {
        $user = User::where(['autologin_token' => $autologinToken])->first();

        if (empty($user)) {
            return null;
        }

        return $user;
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::where([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ])->first();
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Api::$security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        if (!$this->validatePasswordFormat($password)) {
            throw new BadRequestException(Api::t('error.account.password_check'));
        }
        $this->password_hash = Api::$security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Api::$security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Api::$security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     * Generates new autologin hash
     */
    public function generateAutologinToken()
    {
        $this->autologin_token = Api::$security->generateRandomString() . '_' . time();
    }

    /**
     * Set newsletter
     * @param \api\common\models\Newsletter
     */
    public function setNewsletter(Newsletter $newsletter)
    {
        $this->newsletter = $newsletter;
        return $newsletter;
    }

    /**
     * Validate password format (length, hardness, ...)
     * @todo Improve this function
     * @param string $password
     * @return bool
     */
    public static function validatePasswordFormat($password): bool
    {
        if (8 > strlen($password)) {
            return false;
        }

        // list banned passwords
        // @TODO export list in a more convenient place?
        $bannedPassword = [
            '12345678',
            'azertyui',
            'qwertyui',
        ];

        if (in_array($password, $bannedPassword)) {
            return false;
        }

        return true;
    }

    /**
     * Generate access token
     * Get last existing one if exists and update it's expires value if not permanent
     */
    public function generateAccessToken()
    {
        // @TODO check if it's better to use $user->token
        $oAuthAccessTokens = OAuthAccessToken::where('user_id', '=', $this->id)
            ->orderBy('expires', 'desc')
            ->get();

        if ($oAuthAccessTokens->isEmpty()) {
            $now = new \DateTime;
            $tomorrow = $now;
            $tomorrow->add(new \DateInterval('P1D'));
            $accessToken = OAuthAccessToken::generateAccessToken();

            $accessTokenModel = new OAuthAccessToken;
            $accessTokenModel->access_token = $accessToken;
            $accessTokenModel->client_id = $this->email;
            $accessTokenModel->user_id = $this->id;
            $accessTokenModel->expires = $tomorrow->format('Y-m-d H:i:s');
            $accessTokenModel->save();

        // don't update expires date if token is permanent
        } elseif ('permanent' !== $oAuthAccessTokens[0]->type) {
            $now = new \DateTime;
            $tomorrow = $now;
            $tomorrow->add(new \DateInterval('P1D'));
            OAuthAccessToken::where(['user_id' => $this->id])
                ->update([
                    'expires' => $tomorrow->format('Y-m-d H:i:s')
                ]);

            $accessToken = $oAuthAccessTokens[0]->access_token;

            // if old access token exists, use it instead of using a new one
            // to prevent disconnecting other applications
            // @TODO find an other solution for security purpose
            if (1 < count($oAuthAccessTokens)) {
                // use the last existing one
                // @TODO only if not expired
                $date1 = new \DateTime;
                $date2 = date_create($oAuthAccessTokens[1]->expires);
                $diff = $date1->diff($date2);

                if ((bool) $diff->invert) {
                    unset($oAuthAccessTokens[0]);

                // else delete new token and only keep the last exiting token
                } else {
                    $oAuthAccessTokens[1]->expires = $oAuthAccessTokens[0]->expires;
                    $oAuthAccessTokens[1]->save();

                    unset($oAuthAccessTokens[1]);
                }

                foreach ($oAuthAccessTokens as $token) {
                    $token->delete();
                }
            }
        }
    }

    /**
     * Update last visit date
     */
    public function updateLastVist()
    {
        $data = new \DateTime;
        $this->lastvisit_at = $data->format('Y-m-d H:i:s');
        $this->save();
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'id'                    => Api::t('field.id'),
            'username'              => Api::t('field.username'),
            'password'              => Api::t('field.password'),
            'password_hash'         => Api::t('field.password_hash'),
            'password_reset_token'  => Api::t('field.password_reset_token'),
            'autologin_token'       => Api::t('field.autologin_token'),
            'email'                 => Api::t('field.email'),
            'activkey'              => Api::t('field.activkey'),
            'superuser'             => Api::t('field.superuser'),
            'status'                => Api::t('field.status'),
            'ip'                    => Api::t('field.ip'),
            'create_at'             => Api::t('field.create_at'),
            'created_at'            => Api::t('field.created_at'),
            'updated_at'            => Api::t('field.updated_at'),
            'lastvisit_at'          => Api::t('field.lastvisit_at'),
            'social_provider'       => Api::t('field.social_provider'),
            'social_identifier'     => Api::t('field.social_identifier'),
            'social_email'          => Api::t('field.social_email'),
            'family_id'             => Api::t('field.family_id'),
            'confirmation'          => Api::t('field.confirmation'),
            'extension_installed'   => Api::t('field.extension_installed'),
            'extension_activation'  => Api::t('field.extension_activation'),
            'browser'               => Api::t('field.browser'),
            'got_free_consultation' => Api::t('field.got_free_consultation'),
            'opt_out_analytics'     => Api::t('field.opt_out_analytics'),
            'auth_key'              => Api::t('field.auth_key'),
            'locale'                => Api::t('field.locale'),
            'origin'                => Api::t('field.origin'),
            'partner_sku'           => Api::t('field.partner_sku'),
            'tutorials'             => Api::t('field.tutorials'),
        ];
    }
}
