<?php

/**
 * Command devops/kubernetes/get/secrets
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\Secrets\Secret;
use Phoundation\Virtualization\Kubernetes\Secrets\Secrets;

CliDocumentation::setUsage('./pho devops kubernetes get secrets');

CliDocumentation::setHelp('This command returns the available kubernetes secrets');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('name')->isOptional()->matchesRegex('/^[a-z0-9-]+$/')
    ->validate();

if ($argv['name']) {
    // Display the specified name only
    echo Secret::new($argv['name'])->getYaml();
} else {
    // Display all deployments
    $output = Secrets::new();
    Cli::displayTable($output);
}
