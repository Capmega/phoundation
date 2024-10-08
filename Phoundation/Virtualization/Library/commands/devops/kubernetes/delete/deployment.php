<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\Deployments\Deployment;


/**
 * Script devops/kubernetes/delete/deployment
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops kubernetes delete deployment');
CliDocumentation::setHelp('This command deletes the specified Kubernetes deployments');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('name', true)->matchesRegex('/^[a-z0-9-]+$/')
    ->validate();


// Create new deployment and apply it
$deployment = Deployment::new($argv['name'])->delete();
