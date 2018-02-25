<?php
/**
 * Model for working with user documentation token
 *
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\models;

class UserApiKey extends Db
{
    /**
     * @inheritdoc
     */
    public static $tableName = 'user_api_key';

    /**
     * @inheritdoc
     */
    public $timestamps = false;

    /**
     * @inheritdoc
     */
    protected static $rules = [
        'id'         => ['integer'],
        'user_id'    => ['required', 'integer'],
        'api_key_id' => ['required', 'integer'],
    ];

    /**
     * Get user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'user_id'    => 'User ID',
            'api_key_id' => 'API key ID',
        ];
    }
}
