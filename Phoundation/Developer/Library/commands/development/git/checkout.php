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


CliDocumentation::setUsage('./pho development git checkout [BRANCH / FILE]
./pho system dev git checkout [BRANCH / FILE]');

CliDocumentation::setHelp('This command is an interface to the git command through Phoundation. Its not really needed -nor
useful- beyond testing the git library');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('branch_or_file', true)->sanitizeFile()
                     ->validate();


Git::new(FsDirectory::getRootObject())->checkout($argv['branch_or_file']);
