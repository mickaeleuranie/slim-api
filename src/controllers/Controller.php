<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\controllers;

use Slim\Container as ContainerInterface;

class Controller
{
    protected $container;

    public function accessRules()
    {
        return [];
    }

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
