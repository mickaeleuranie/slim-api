<?php
/**
 * Model for working with user documentation token
 *
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\models;

class ApiKey extends Db
{
    /**
     * @inheritdoc
     */
    public static $tableName = 'api_key';

    /**
     * @inheritdoc
     */
    public $timestamps = false;

    /**
     * @inheritdoc
     */
    protected static $rules = [
        'id'   => ['integer'],
        'slug' => ['required', 'string', 'max:255'],
        'key'  => ['required', 'integer', 'nullable', 'max:30'],
    ];

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'id'   => 'ID',
            'slug' => 'Slug',
            'key'  => 'API key',
        ];
    }
}
