<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\exceptions;

use Exception;

class ForbiddenException extends Exception
{
    protected $code = 403;
    protected $message = 'Forbidden';

    public function __construct($message = null)
    {
        if (!empty($message)) {
            $this->message = $message;
        }
    }
}
