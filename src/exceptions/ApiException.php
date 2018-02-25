<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\exceptions;

use Exception;

class ApiException extends Exception
{
    protected $code = 500;
    protected $message = 'Internal error';

    public function __construct($message = null, $code = null)
    {
        if (!empty($message)) {
            $this->message = $message;
        }
        if (!empty($code)) {
            $this->code = $code;
        }
    }
}
