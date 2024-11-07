<?php

/**
 * Command system suspend
 *
 * This command will suspend this computer
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


CliDocumentation::setUsage('./pho system suspend');

CliDocumentation::setHelp('This command will suspend this computer


ARGUMENTS


-');


// Get arguments
$argv = ArgvValidator::new()
                     ->validate();


SystemCtl::new()->suspend();