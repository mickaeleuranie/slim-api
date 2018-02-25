<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\exceptions;

use Exception;

class NotFoundException extends Exception
{
    protected $code = 404;
    protected $message = 'Not found';

    public function __construct($message = null)
    {
        if (!empty($message)) {
            $this->message = $message;
        }
    }
}
