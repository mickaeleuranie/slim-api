<?php

/**
 * Model for working with signup data
 * SignupSocialForm is the data structure for keeping
 * user registration form data.
 *
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\models;

class SignupSocialForm extends User
{
    protected static $rules = [
        'username'          => ['required', 'unique:user'],
        'social_provider'   => ['required'],
        'social_identifier' => ['required'],
        'email'             => ['email', 'nullable'],
        'social_email'      => ['required', 'email'],
    ];
}
