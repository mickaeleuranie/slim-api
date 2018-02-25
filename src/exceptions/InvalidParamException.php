<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\exceptions;

use Exception;

class InvalidParamException extends BadRequestException
{
    protected $code = 400;
    protected $message = 'Bad parameters';

    public function __construct($message = null)
    {
        if (!empty($message)) {
            $this->message = $message;
        }
    }
}
