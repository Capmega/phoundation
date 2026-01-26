<?php

/**
 * Command project autocomplete update
 *
 * This command will update the BaSH autocomplete code to the latest version
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Cli\CliAutoComplete;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho project autocomplete update');

CliDocumentation::setHelp('


ARGUMENTS


-');


// Do not allow any arguments at all
ArgvValidator::new()->validate();


// Setup auto complete
CliAutoComplete::setup(true);
