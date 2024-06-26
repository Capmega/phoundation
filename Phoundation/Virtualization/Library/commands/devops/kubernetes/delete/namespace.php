<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\KubernetesNamespaces\KubernetesNamespace;


/**
 * Command devops/kubernetes/delete/namespace
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops kubernetes delete namespace');
CliDocumentation::setHelp('This command deletes the specified Kubernetes namespaces');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('name', true)->matchesRegex('/^[a-z0-9-]+$/')
    ->validate();


// Create new namespace and apply it
$namespace = KubernetesNamespace::new($argv['name'])->delete();
