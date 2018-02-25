<?php


/**
 * Model for working with email updates
 *
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\models;

class EmailUpdate extends Db
{
    /**
     * @inheritdoc
     */
    public static $tableName = 'email_update';

    /**
     * @inheritdoc
     */
    protected static $rules = [
        'user_id' => ['required', 'integer', 'unique:email_update'],
        'key'     => ['required', 'string', 'max:30'],
        'visited' => ['boolean'],
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
            'id'      => 'ID',
            'user_id' => 'User',
            'email'   => 'Email',
            'key'     => 'Security key',
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
