<?php

/**
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command is an interface to the git command through Phoundation. Its not really needed -nor useful- beyond testing
 * the git library
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
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Filesystem\PhoDirectory;


CliDocumentation::setUsage('./pho development git diff PATHS
./pho dev git diff');

CliDocumentation::setHelp('This command is an interface to the git command through Phoundation. Its not really needed -nor
useful- beyond testing the git library');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('files')->sanitizeForceArray()->forEachField()->sanitizePath()
                     ->validate();


Log::cli(Git::new(PhoDirectory::newRoot())->getDiff($argv['files']));
