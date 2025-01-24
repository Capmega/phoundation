<?php

/**
 * Command system autocomplete
 *
 * This command ensures autocomplete will be available
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliAutoComplete;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Date\DateTime;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\FsRestrictions;


CliDocumentation::setUsage('./pho system clear [OPTIONS]
');

CliDocumentation::setHelp('This command ensures autocomplete will be available


ARGUMENTS


-');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->validate();


// Go!
CliAutoComplete::ensureAvailable();
