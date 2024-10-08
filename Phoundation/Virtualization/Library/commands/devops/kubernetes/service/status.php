<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\Kubernetes;
use Phoundation\Virtualization\Kubernetes\MiniKube;


/**
 * Script devops/kubernetes/service/status
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops kubernetes service status');
CliDocumentation::setHelp('This command will status the kubernetes cluster service');


// Validate arguments
$argv = ArgvValidator::new()->validate();

// Get and display status
$status = Kubernetes::new()->getStatus();

foreach ($status as $line) {
    Log::cli($line);
}
