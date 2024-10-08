<?php

declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\Ingresses\Ingress;
use Phoundation\Virtualization\Kubernetes\Ingresses\Ingresses;


/**
 * Script devops/kubernetes/get/ingresses
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops kubernetes get ingresses');
CliDocumentation::setHelp('This command returns the available kubernetes ingresses');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('name')->isOptional()->matchesRegex('/^[a-z0-9-]+$/')
    ->validate();

if ($argv['name']) {
    // Display the specified name only
    echo Ingress::new($argv['name'])->getYaml();
} else {
    // Display all deployments
    $output = Ingresses::new();
    Cli::displayTable($output);
}
