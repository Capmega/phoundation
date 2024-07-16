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
 * @category  Function reference
 * @package   Phoundation\Development
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Filesystem\FsDirectory;

CliDocumentation::setUsage('./pho development git rebase BRANCH
./pho system dev git rebase BRANCH');

CliDocumentation::setHelp('This command is an interface to the git command through Phoundation. Its not really needed -nor
useful- beyond testing the git library');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-b,--branch', true)->isDirectory()
                     ->validate();


Git::new(FsDirectory::getRoot())->rebase($argv['branch']);
