<?php

/**
 * Command system start
 *
 * This command will start the specified service
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Os\Processes\Commands\SystemCtl;


CliDocumentation::setUsage('./pho systemd start SERVICE');

CliDocumentation::setHelp('This command will start the specified service 


ARGUMENTS


SERVICE                                 The service that should be started');


// Get arguments
$argv = ArgvValidator::new()
                     ->select('service')->hasMinCharacters(2)->hasMaxCharacters(64)->isVariable()
                     ->validate();


SystemCtl::new()
         ->setOsProcessName($argv['service'])
         ->start();