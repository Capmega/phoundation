<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\Ingresses\Ingress;


/**
 * Script devops/kubernetes/create/ingress
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops kubernetes create ingress');
CliDocumentation::setHelp('This command creates a Kubernetes ingress using a ingress file');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('-n,--name', true)->isOptional(strtolower(PROJECT) . '-web')->matchesRegex('/^[a-z0-9-]+$/')
    ->select('-s,--selectors', true)->sanitizeForceArray()->each()->matchesRegex('/^[a-z0-9-]+=[a-z0-9-]+$/')
    ->validate();


// Create new ingress file and apply it
$ingress = Ingress::new()
    ->setName($argv['name'])
    ->save()
    ->apply();
