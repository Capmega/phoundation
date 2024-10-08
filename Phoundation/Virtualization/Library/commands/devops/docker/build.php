<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Docker\DockerFile;


/**
 * Script devops/docker/build
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops docker build');
CliDocumentation::setHelp('This command can build docker images');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('-p,--path', true)->isOptional(DIRECTORY_ROOT)->isFile()
    ->select('-i,--image', true)->isOptional(strtolower(PROJECT) . '-default')->matchesRegex('/^[a-z-]+$/')
    ->validate();


// Build the docker image
DockerFile::new($argv['image'], $argv['path'])
    ->setRestrictions(DIRECTORY_ROOT, true)
    ->writeConfig()
    ->render();
