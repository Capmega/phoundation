<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Utils\Strings;


/**
 * Command system/temporary/clear
 *
 * This script can be used to test the authentication for the specified user
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

CliDocumentation::setUsage('./pho system temporary clear PATH [OPTIONS]
./pho system temporary clear PATH --public
./pho system temporary clear PATH ');

CliDocumentation::setHelp('This command will clear the specified temporary directory


ARGUMENTS


[-p,--public]                           If specified, a public temporary directory will be cleared');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('path')->isOptional()->isFile()
                     ->select('-p,--public')->isOptional()->isBoolean()
                     ->validate();


// Ensure that the path starts from the correct temporary directory
$argv['path'] = Strings::from($argv['path'], ($argv['public'] ? DIRECTORY_PUBTMP : DIRECTORY_TMP));


// Clear the specified temporary directory and we're done
FsFile::new(($argv['public'] ? DIRECTORY_PUBTMP : DIRECTORY_TMP) . $argv['path'], FsRestrictions::getWritable($argv['public'] ? DIRECTORY_PUBTMP : DIRECTORY_TMP))->delete();
