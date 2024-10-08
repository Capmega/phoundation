<?php

/**
 * Command system setup
 *
 * This is the setup script for the project. This script will be the first script to be run to set up your system
 *
 * To be able to set up, one conditions must be met: There is no configuration file available
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Core
 */

declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Plugins\Plugins;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\ArrayValidator;
use Phoundation\Developer\Project\Project;
use Phoundation\Exception\OutOfBoundsException;

CliDocumentation::setUsage('./pho system setup [OPTIONS]
./pho system setup --no-password-validation
./pho system setup --force');

CliDocumentation::setHelp('This command allows you to setup a new project


ARGUMENTS


[--import]                              Run import for all libraries that support it. This will automatically import all
                                        data for all systems. Since this requires (amongst things) downloading and
                                        importing (sometimes) very large data sets, this may take a little while

[--project]                             Setup the entire project

[--no-validation]                       (System argument) If specified, the validation system will not cause any
                                        validation errors and all data will be accepted. Please note that this may cause
                                        unpredictable behaviour and errors depending on input. Use only for testing!

[--no-password-validation]              Will only not validate passwords and allow unsafe or even empty passwords. Use
                                        only for testing!

[-F / --force]                          (System argument) Run the setup in FORCE mode, which will remove any previously
                                        set up project and start a new project from scratch');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('--no-password-validation')->isOptional(false)->isBoolean()
                     ->select('--project')->isOptional(false)->isBoolean()
                     ->select('--import')->isOptional(false)->isBoolean()
                     ->select('--demo')->isOptional(false)->isBoolean()
                     ->select('--min', true)->isOptional(0)->isNatural()
                     ->select('--max', true)->isOptional(100)->isNatural()
                     ->validate();


// Validate and create the project
if (!Project::projectFileExists()) {
    $create = true;

} elseif ($argv['project']) {
    if (!FORCE) {
        throw new OutOfBoundsException(tr('Cannot setup system, project file "config/project" already exists. Please re-run this script with -F / --force option'));
    }

    Project::remove();
    $create = true;

} else {
    Project::load();
}


// Get setup variables
$config = [];

if (isset($create)) {
    $config['project'] = Cli::readInput('Please specify the project name:', 'Phoundation');

} else {
    $config['project'] = Project::getName();
}

$config['environment']    = Cli::readInput('Please specify the environment you wish to set up:', 'local');
$config['domain']         = Cli::readInput('Please specify the project domain name:', 'localhost');
$config['database_host']  = Cli::readInput('Please specify the core database host:', 'localhost');
$config['database_name']  = Cli::readInput('Please specify the core database name:', 'phoundation');
$config['database_user']  = Cli::readInput('Please specify the core database user:', 'phoundation');
$config['database_pass1'] = Cli::readPassword('Please specify the core database password:');
$config['database_pass2'] = Cli::readPassword('Please repeat the core database password:');
$config['admin_email']    = Cli::readInput('Please specify the administrator email:');
$config['admin_pass1']    = Cli::readPassword('Please specify the administrator password:');
$config['admin_pass2']    = Cli::readPassword('Please repeat the administrator password:');


// Validate setup parameters
Project::validate(ArrayValidator::new($config));


// Create the project and set the environment
if (isset($create)) {
    Project::create($config['project']);
}

Project::setEnvironment($config['environment']);


// Yay! We can safely set up this environment. Create a basic configuration file from a few basic questions.
$configuration = Project::getEnvironment()->getConfiguration();
$configuration->setProject($config['project']);
$configuration->setDomain($config['domain']);
$configuration->setEmail($config['admin_email']);
$configuration->setPassword($config['admin_pass1']);
$configuration->getDatabase()->setHost($config['database_host']);
$configuration->getDatabase()->setName($config['database_name']);
$configuration->getDatabase()->setUser($config['database_user']);
$configuration->getDatabase()->setPass($config['database_pass1']);


// Run setup
Project::setup();
Plugins::setup();


// Run import?
if ($argv['import']) {
    Project::import($argv['demo'], $argv['min'], $argv['max']);
}
