<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\exceptions;

use Exception;

class UnauthorizedException extends Exception
{
    protected $code = 401;
    protected $message = 'Unauthorized';

    public function __construct($message = null)
    {
        if (!empty($message)) {
            $this->message = $message;
        }
    }
}
