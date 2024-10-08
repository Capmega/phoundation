<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Virtualization\Kubernetes\Kubernetes;
use Phoundation\Virtualization\Kubernetes\MiniKube;


/**
 * Script devops/kubernetes/service/stop
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho devops kubernetes service stop');
CliDocumentation::setHelp('This command will stop the kubernetes cluster service');


// Validate arguments
$argv = ArgvValidator::new()->validate();

Kubernetes::new()->dashboard();
