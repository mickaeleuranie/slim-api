<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\interfaces;

interface DbInterface
{
    public function attributeLabels();
    public function beforeSave();
    public function afterSave();
}
