<?php

/**
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command is an interface to the git command through Phoundation. Its not really needed -nor useful- beyond testing
 * the git library
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Development
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Filesystem\FsDirectory;


CliDocumentation::setUsage('./pho development git reset [BRANCH] [FILE] [--hard]
./pho system dev git reset [BRANCH] [FILE] [--hard]');

CliDocumentation::setHelp('This command is an interface to the git command through Phoundation. Its not really needed -nor
useful- beyond testing the git library');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('branch', true)->isVariable()
                     ->select('files', true)->isOptional()->sanitizeForceArray()->each()->sanitizeFile()
                     ->select('--hard')->isOptional()->isBoolean()
                     ->validate();

Git::new(FsDirectory::getRootObject())->reset($argv['branch'], get_null($argv['files']));
