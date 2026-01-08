<?php

/**
 * Command developer git repositories changed
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will display either nothing when there are no changes in any of the known repositories, or 1 when there are 1 or more changes in any of the
 * known repositories
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Development
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Repositories\Repositories;


// Start documentation
CliDocumentation::setAutoComplete();

CliDocumentation::setUsage('./pho development repositories changed');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will display either nothing when there are no changes in any of the known repositories, or 1 when there are 1 or more changes in any of the known
repositories 


ARGUMENTS


-'));


// Get command line arguments
$argv = ArgvValidator::new()->validate();


// Display "1" if there are any changes
if (Repositories::new()->load()->getStatusObject()->isNotEmpty()) {
    Log::cli(1);
}
