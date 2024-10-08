<?php

declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\Services\Service;


/**
 * Script devops/kubernetes/create/service
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops kubernetes create service');
CliDocumentation::setHelp('This command creates a Kubernetes service using a service file');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('-n,--name', true)->isOptional(strtolower(PROJECT) . '-web')->matchesRegex('/^[a-z0-9-]+$/')
    ->select('-s,--selectors', true)->sanitizeForceArray()->each()->matchesRegex('/^[a-z0-9-]+=[a-z0-9-]+$/')
    ->validate();


// Create new service file and apply it
$service = Service::new()
    ->setName($argv['name'])
    ->setSelectors($argv['selectors'])
    ->save()
    ->apply();
