<?php

/**
 * Command developer is-root
 *
 * This command will display 1 if it is run as root, 0 if it is not root
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Utils\Strings;


CliDocumentation::setAutoComplete();

CliDocumentation::setUsage('./pho developer is-root');

CliDocumentation::setHelp('This command will display 1 if it is run as root, 0 if it is not root


ARGUMENTS 


-');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('--root', true)->isOptional('1')->hasMaxCharacters(256)
                     ->select('--user', true)->isOptional('0')->hasMaxCharacters(256)
                     ->validate();


// Copy the file to the correct remote repositories
Log::cli(Strings::fromBoolean(Core::processIsRoot(), $argv['root'], '0'));