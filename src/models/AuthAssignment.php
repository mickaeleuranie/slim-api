<?php
/**
 * Model for working with auth_assignment
 *
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\models;

use \api\models\Db;

class AuthAssignment extends Db
{

    /**
     * @inheritdoc
     */
    protected static $tableName = 'auth_assignment';

    /**
     * @inheritdoc
     */
    public $timestamps = false;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['item_name', 'user_id'], 'required'],
            [['user_id'], 'integer', 'integerOnly' => true],
            [['item_name'], 'string', 'max' => 64],
        ];
    }

    /**
     * Get related user
     * @return User
     */
    public function getUser()
    {
        return $this->hasOne('\api\models\User');
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'item_name' => 'Itemname',
            'user_id'   => 'Userid',
            'bizrule'   => 'Bizrule',
            'data'      => 'Data',
        ];
    }
}
