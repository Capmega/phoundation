<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\Services\Service;


/**
 * Script devops/kubernetes/delete/service
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops kubernetes delete service');
CliDocumentation::setHelp('This command deletes the specified Kubernetes service');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('name', true)->matchesRegex('/^[a-z0-9-]+$/')
    ->validate();


// Create new service and apply it
$service = Service::new($argv['name'])->delete();
