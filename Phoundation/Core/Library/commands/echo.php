<?php

/**
 * Command "echo"
 *
 * The echo script will echo the specified text. This command is mostly used for debugging purposes.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho echo TEXT');

CliDocumentation::setHelp('The echo script will echo the specified text. This command is mostly used for debugging 
purposes.


ARGUMENTS


-');


// Get arguments
$argv = ArgvValidator::new()
                     ->select('text')->isString()
                     ->validate();


Log::cli($argv['text']);
