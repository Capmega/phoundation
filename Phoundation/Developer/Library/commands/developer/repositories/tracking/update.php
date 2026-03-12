<?php

/**
 * Command developer repositories tracking update
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will list the add for all known phoundation repositories
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Repositories\Repositories;


// Start documentation
CliDocumentation::setAutoComplete();

CliDocumentation::setUsage('./pho developer repositories tracking update
./pho dv rp tr up');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will pull all branches that are behind the tracking branches  


ARGUMENTS


-'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->validate();


// Update all branches
Repositories::new()->load()->ensureAllBranchesAreCurrent();
