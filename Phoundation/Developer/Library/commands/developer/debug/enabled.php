<?php

/**
 * Command developer debug enabled
 *
 * This command will print 1 if debug is enabled, or 0 if not
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Debug\Debug;


CliDocumentation::setHelp('This command will print 1 if debug is enabled, or 0 if not');

CliDocumentation::setUsage('./pho dev debug enabled
./pho developer debug enabled');


// No arguments allowed
$argv = ArgvValidator::new()
                     ->validate();


// Echo result
Log::cli(Debug::isEnabled() ? '1' : '0');
