<?php

/**
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will copy the specified file back to your phoundation development installation
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Development
 */


declare(strict_types=1);

use Phoundation\Cli\CliCommand;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Phoundation\Phoundation;
use Phoundation\Developer\Phoundation\Plugins;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;


CliDocumentation::setUsage('./pho project copy FILE');

CliDocumentation::setHelp('This command will copy the specified library file directly to your Phoundation installation

If, for example, you specify Phoundation/Web/Page.php as the file, it will copy this file back to your Phoundation
installation in the exact same location


ARGUMENTS


FILE                                    The file to copy

-b, --branch BRANCH                     Change the Phoundation to the specified branch

-c, --allow-changes                     The file to copy');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-b,--branch', true)->isOptional()->isVariableName()
                     ->select('-c,--allow-changes')->isOptional(false)->isBoolean()
                     ->selectAll('files')->eachField()->sanitizePath(FsDirectory::newRootObject(false))
                     ->validate();


// Copy the file to either Phoundation install or Phoundation plugins install
Phoundation::new()->copy($argv['files'], $argv['branch'], !$argv['allow_changes']);
Plugins::new()->copy($argv['files'], $argv['branch'], !$argv['allow_changes']);
