<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\KubernetesNamespaces\KubernetesNamespace;


/**
 * Script devops/kubernetes/create/namespaces
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops kubernetes create namespaces');
CliDocumentation::setHelp('This command creates a Kubernetes namespace using a namespace file');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('-n,--name', true)->isOptional(strtolower(PROJECT) . '-web')->matchesRegex('/^[a-z0-9-]+$/')
    ->select('-i,--image', true)->isOptional('localhost:5000/' . strtolower(PROJECT) . '-default')->matchesRegex('/^[a-z0-9-]+$/')
    ->select('-r,--replicas', true)->isOptional(1)->isBetween(1, 1000000)
    ->validate();


// Create new namespace file and apply it
$namespace = KubernetesNamespace::new()
    ->setName($argv['name'])
    ->setImage($argv['image'])
    ->setReplicas($argv['replicas'])
    ->save()
    ->apply();
