<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\Ingresses\Ingress;


/**
 * Script devops/kubernetes/delete/ingress
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops kubernetes delete ingress');
CliDocumentation::setHelp('This command deletes the specified Kubernetes ingress');


// Validate arguments
$argv = ArgvValidator::new()
    ->select('name', true)->matchesRegex('/^[a-z0-9-]+$/')
    ->validate();


// Create new ingress and apply it
$ingress = Ingress::new($argv['name'])->delete();
