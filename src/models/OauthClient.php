<?php

namespace api\models;

use api\Api;
use api\models\OauthClients;
use api\models\User;

/**
 * This is the model class for table "oauth_clients".
 */
class OauthClient extends Db
{
    /**
     * @inheritdoc
     */
    protected static $tableName = 'oauth_clients';

    /**
     * @inheritdoc
     */
    protected $primaryKey = 'client_id';

    /**
     * @inheritdoc
     */
    public $timestamps = false;

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'client_id'     => Api::t('field.client_id'),
            'client_secret' => Api::t('field.client_secret'),
            'redirect_uri'  => Api::t('field.redirect_uri'),
            'grant_types'   => Api::t('field.grant_types'),
            'scope'         => Api::t('field.scope'),
            'user_id'       => Api::t('field.user_id'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected static $rules = [
        'client_id'     => ['string'],
        'client_secret' => ['string'],
        'redirect_uri'  => ['string'],
        'grant_types'   => ['string'],
        'scope'         => ['string', 'nullable'],
        'user_id'       => ['integer'],
    ];

    /**
     * @return \yii\db\ActiveQuery
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function tokens()
    {
        return $this->hasMay(OauthAccessToken::class, 'client_id', 'client_id');
    }

    /**
     * Delete related tokens
     */
    public function beforeDelete()
    {
        if (parent::beforeDelete()) {
            foreach ($this->tokens as $token) {
                $token->delete();
            }
            return true;
        }
        return false;
    }
}
