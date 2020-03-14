<?php

use SuiteCRM\Custom\Command\TestQueueListener;
use Symfony\Component\Console\Application;

if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}

require_once 'include/entryPoint.php';

$application = new Application();

$application->add(new TestQueueListener());

$application->run();
