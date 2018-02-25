<?php

/**
 * Model for working with password reset
 *
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\models;

class PasswordReset extends Db
{
    /**
     * @inheritdoc
     */
    public static $tableName = 'password_reset';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'activation_key'], 'required'],
            [['user_id'], 'integer', 'integerOnly' => true],
            [['user_id'], 'unique'],
            [['visited'], 'boolean'],
            [['activation_key'], 'string', 'max' => 30],
            [['id', 'user_id', 'time'], 'safe', 'on'=>'search'],
        ];
    }

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
            'id'      => 'ID',
            'user_id' => 'User',
            'email'   => 'Email',
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        $date = new \DateTime();
        $this->time = $date->format('Y-m-d H:i:s');
        return true;
    }
}
