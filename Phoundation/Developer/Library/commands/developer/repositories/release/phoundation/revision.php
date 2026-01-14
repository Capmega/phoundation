<?php

/**
 * Command developer repositories release phoundation revision
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will release an upgraded version with an increased revision number for each of your phoundation repositories
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
use Phoundation\Developer\Phoundation\Enums\EnumPhoundationClass;
use Phoundation\Developer\Versioning\Repositories\Repositories;
use Phoundation\Filesystem\PhoDirectory;


// Start documentation
CliDocumentation::setAutoComplete([
    'positions' => [
        0 => true
    ]
]);

CliDocumentation::setUsage('./pho development repositories release phoundation revision
./pho development rp rl ph rv');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will release an upgraded version with an increased revision number for each of your phoundation repositories 


ARGUMENTS


-'));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-n,--number')->isOptional()->isPositive()
                     ->validate();


// Synchronize all available repositories
$o_repositories = Repositories::new()->load();

Log::cli(ts('Releasing revision, this might take a few seconds...'), 'action');

$o_repositories->releaseRevision(EnumPhoundationClass::phoundation, $argv['number']);
