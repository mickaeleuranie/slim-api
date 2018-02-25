<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\exceptions;

use Exception;

class BadRequestException extends Exception
{
    protected $code = 400;
    protected $message = 'Bad request exception';

    public function __construct($message = null)
    {
        if (!empty($message)) {
            $this->message = $message;
        }
    }
}
