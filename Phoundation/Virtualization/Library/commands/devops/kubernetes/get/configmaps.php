<?php

declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\ConfigMaps\ConfigMap;
use Phoundation\Virtualization\Kubernetes\ConfigMaps\ConfigMaps;


/**
 * Script devops/kubernetes/configmap/get
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops kubernetes configmap get');
CliDocumentation::setHelp('This command returns the available kubernetes configmap');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('name')->isOptional()->matchesRegex('/^[a-z0-9-]+$/')
    ->validate();

if ($argv['name']) {
    // Display the specified deployment only
    echo ConfigMap::new($argv['name'])->getYaml();
} else {
    // Display all configmap
    $output = ConfigMaps::new();
    Cli::displayTable($output);
}
