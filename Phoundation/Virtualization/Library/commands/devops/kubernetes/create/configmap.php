<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\ConfigMaps\ConfigMap;


/**
 * Script devops/kubernetes/create/deployments
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops kubernetes create deployments');
CliDocumentation::setHelp('This command creates a Kubernetes deployment using a deployment file');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('-n,--name', true)->isOptional(strtolower(PROJECT) . '-web')->matchesRegex('/^[a-z0-9-]+$/')
    ->select('-d,--data', true)->sanitizeForceArray()->each()->matchesRegex('/^[a-z0-9-]+=[a-z0-9-]+$/')
    ->validate();


// Create new deployment file and apply it
$deployment = ConfigMap::new()
    ->setName($argv['name'])
    ->setData($argv['data'])
    ->save()
    ->apply();
