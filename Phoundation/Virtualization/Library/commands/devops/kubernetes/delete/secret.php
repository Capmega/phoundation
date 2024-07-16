<?php

/**
 * Command devops/kubernetes/secrets/create
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\Secrets\Secret;

CliDocumentation::setUsage('./pho devops kubernetes delete secret');

CliDocumentation::setHelp('This command creates Kubernetes secrets');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('name', true)->matchesRegex('/^[a-z0-9-]+$/')
    ->validate();


// Create new secret and apply it
$secret = Secret::new($argv['name'])->delete();
