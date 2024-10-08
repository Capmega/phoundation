<?php

declare(strict_types=1);

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\KubernetesNamespaces\KubernetesNamespace;
use Phoundation\Virtualization\Kubernetes\KubernetesNamespaces\KubernetesNamespaces;


/**
 * Script devops/kubernetes/namespaces/get
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops kubernetes namespaces get');
CliDocumentation::setHelp('This command returns the available kubernetes namespaces');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('name')->isOptional()->matchesRegex('/^[a-z0-9-]+$/')
    ->validate();

if ($argv['name']) {
    // Display the specified namespace only
    echo KubernetesNamespace::new($argv['name'])->getYaml();
} else {
    // Display all namespaces
    $output = KubernetesNamespaces::new();
    Cli::displayTable($output);
}
