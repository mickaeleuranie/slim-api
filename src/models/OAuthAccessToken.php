<?php

namespace api\models;

use api\Api;

class OAuthAccessToken extends Db
{
    /**
     * @inheritdoc
     */
    protected $primaryKey = 'access_token';

    /**
     * @inheritdoc
     */
    public $timestamps = false;

    /**
     * @inheritdoc
     */
    public $incrementing = false;

    /**
     * @inheritdoc
     */
    protected static $tableName = 'oauth_access_tokens';

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'access_token' => Api::t('field.access_token'),
            'client_id'    => Api::t('field.client_id'),
            'user_id'      => Api::t('field.user_id'),
            'expires'      => Api::t('field.expires'),
            'scope'        => Api::t('field.scope'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected static $rules = [
        'access_token'         => ['string'],
        'client_id'            => ['string'],
        'user_id'              => ['integer'],
        'expires'              => ['date'],
        'scope'                => ['string', 'nullable'],
    ];

    public static function getClass()
    {
        return __CLASS__;
    }

    /**
     * Get the user that owns the phone.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getAll()
    {
        $user = new User;
        $query = $user->pdo
            ->select(['*'])
            ->from($this->tableName)
        ;
        $stmt = $query->execute();

        return $stmt->fetch();
    }

    /**
     * Generate access token
     * @param integer $length
     */
    public static function generateAccessToken($length = 40)
    {
        if (@file_exists('/dev/urandom')) { // Get 100 bytes of random data
            $randomData = file_get_contents('/dev/urandom', false, null, 0, 100) . uniqid(mt_rand(), true);
        } else {
            $randomData = mt_rand() . mt_rand() . mt_rand() . mt_rand() . microtime(true) . uniqid(mt_rand(), true);
        }

        return substr(hash('sha512', $randomData), 0, (int) $length);
    }
}
