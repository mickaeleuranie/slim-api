<?php

/**
 * This file is part of the Avocadoo API package
 *
 * @author Mickaël Euranie <mickael@avocadoo.com>
 * @copyright Avocadoo
 *
 */

namespace tasks;

use api\Api;
use api\exceptions\CliException;
use Interop\Container\ContainerInterface;

class Task
{
    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    public $args;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function __construct(ContainerInterface $container)
    {
        global $argv;

        // access container classes
        // eg $container->get('redis');
        $this->container = $container;

        // set execution locale if is set
        $locale = 'fr';
        foreach ($argv as $key => $arg) {
            if (preg_match('/locale=([a-zA-Z]{2,})/', $arg, $matches)) {
                if (isset($matches[1])) {
                    $locale = $matches[1];
                }
                unset($argv[$key]);
            }

            // handle help command
            if (preg_match('/\-\-help/', $arg, $matches)) {
                $this->help();
            }
        }

        // save arguments
        $this->args = array_slice($argv, 2);

        Api::setLocale($locale);
    }

    /**
     * Display help
     * Needs to be extended in each child class
     */
    protected function help()
    {
        throw new CliException('No help provided for this task');
    }
}
